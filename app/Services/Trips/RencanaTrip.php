<?php

namespace App\Services\Trips;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\TrailFitService;
use App\Services\Trails\StatusJalurRingkas;
use App\Services\Trails\TrailFigures;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Kelas halaman Rencanakan trip (spec v3 bagian 6.8, prototype 08, rencana induk W4 butir 2).
 *
 * Jalur datang dari ?trail= dan hanya jalur terbit yang dapat direncanakan (R-022). Tanpa
 * jalur, halaman menawarkan simpanan pendaki sendiri, bukan seluruh katalog. Jenis trip,
 * tanggal naik, dan tanggal turun dibaca dari URL dan hanya dipakai bila sah, supaya memuat
 * ulang halaman tidak membuang pilihan pendaki dan URL tidak dapat memaksakan tanggal lampau.
 *
 * Kecocokan untuk rencana, jadwal, dan jendela izin dikirim sebagai closure: muat ulang parsial
 * Inertia (only: kecocokan, jadwal, izin) menghitung ulang hanya ketiganya setiap jenis atau
 * tanggal berubah. Kartu jalur dan pilihan jalur juga closure, supaya muat ulang parsial tidak
 * menghitungnya lalu membuangnya (tinjauan Tugas 3 M4). Yang dikirim hanya label, kata, alasan,
 * dan apakah jalur lolos syarat dasar; skor internal berhenti di server (BR-09).
 */
final class RencanaTrip
{
    public function __construct(
        private readonly TrailFitService $trailFit,
        private readonly PermitService $permits,
        private readonly StatusJalurRingkas $statusJalur,
        private readonly ConsiderationService $simpanan,
        private readonly TripReminderScheduler $pengingat,
    ) {}

    /**
     * Batas isian yang sama dengan aturan validasinya, supaya petunjuk di formulir tidak pernah
     * menyebut angka yang berbeda dari yang ditolak server. Satu sumber untuk Rencanakan dan
     * Ubah rencana (tinjauan Tugas 3 M8).
     *
     * @return array{peserta: int, rentangHari: int}
     */
    public static function batasIsian(): array
    {
        return [
            'peserta' => (int) config('hiking.trips.max_participants'),
            'rentangHari' => (int) config('hiking.trips.max_span_days'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function props(User $user, Request $request): array
    {
        $trail = $request->integer('trail') ? Trail::published()->with('mountain')->find($request->integer('trail')) : null;
        $goalId = $request->integer('goal');
        $goal = $goalId ? $user->hikingGoals()->find($goalId) : $user->hikingGoals()->latest()->first();

        // ?jenis= harus string tunggal; ?jenis[]=x mengirim array, dan PHP melarang array
        // dilebur ke parameter bertipe string (TypeError), bukan sekadar peringatan (putaran
        // perbaikan 1 W4 Tugas 3, ruling W4-3c). Nilai bukan-string diam-diam diabaikan, jatuh
        // ke jenis dari tujuan atau preferensi, sama seperti nilai string yang tidak dikenal.
        $jenisMentah = $request->query('jenis');
        $jenis = (is_string($jenisMentah) ? TripType::tryFrom($jenisMentah) : null)
            ?? $goal?->trip_type
            ?? $user->preference?->preferred_trip_type;
        // ?goal= eksplisit membawa tanggal rencananya sendiri (target_date) ketika URL belum
        // membawa tanggal sendiri, supaya pendaki yang datang dari tautan Rekomendasi tidak
        // mengetik ulang tanggal yang sudah ia pilih di sana (ruling W4-3e). Lantai tanggal tetap
        // berlaku lewat tanggalSah(): target_date yang sudah lewat tidak dipakai.
        $tanggal = self::tanggalSah($request->query('tanggal'))
            ?? ($goalId && $goal !== null ? self::tanggalSah($goal->target_date?->toDateString()) : null);
        $selesai = $this->selesai($tanggal, self::tanggalSah($request->query('selesai')), $jenis);

        return [
            'jalur' => fn () => $trail === null ? null : $this->jalur($trail),
            'pilihanJalur' => fn () => $trail === null ? $this->pilihanJalur($user) : [],
            'awal' => [
                'trail_id' => $trail !== null ? (string) $trail->id : '',
                'hiking_goal_id' => $goal?->id,
                'name' => $trail !== null ? 'Pendakian '.$trail->name : '',
                'planned_date' => $tanggal?->toDateString() ?? '',
                'end_date' => $selesai?->toDateString() ?? '',
                'start_time' => '',
                'participants_count' => '1',
                'trip_type' => $jenis?->value ?? '',
                'notes' => $goal?->notes ?? '',
                'reminders_enabled' => false,
            ],
            'jenisTrip' => array_map(fn (TripType $t) => ['nilai' => $t->value, 'label' => $t->labelPendek(), 'keterangan' => $t->lamaHari().' hari', 'lamaHari' => $t->lamaHari()], TripType::cases()),
            'tanggalTerawal' => Timezone::earliestDateInIndonesia(),
            'batas' => self::batasIsian(),
            'zona' => $trail !== null ? Timezone::label(Timezone::forTrail($trail)) : null,
            'jadwalPengingat' => $this->pengingat->keteranganJadwal(),
            'kecocokan' => fn () => $trail === null ? null : $this->kecocokan($user, $trail, $goal, $jenis, $tanggal),
            'jadwal' => fn () => $tanggal === null ? null : $this->jadwal($tanggal, $selesai, $trail),
            'izin' => fn () => $trail === null ? null : $this->izin($trail, $tanggal),
        ];
    }

    /**
     * Tanggal Y-m-d dari URL, hanya bila bentuknya benar dan tidak sebelum hari ini menurut WIB
     * (lantai yang sama dengan StoreTripRequest, IndonesianDateFloorTest).
     */
    private static function tanggalSah(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) || $nilai < Timezone::earliestDateInIndonesia()) {
            return null;
        }

        $tanggal = Carbon::createFromFormat('!Y-m-d', $nilai);

        return $tanggal !== false && $tanggal->toDateString() === $nilai ? $tanggal : null;
    }

    /**
     * Tanggal turun. Pilihan pendaki dari URL hanya dipakai untuk jenis yang menampilkan isian
     * tanggal turun (lebih dari dua hari, Create.tsx), dan hanya bila sah: tidak sebelum
     * tanggal naik dan tidak melebihi rentang maksimum. Jenis lain memakai lama bawaannya, jadi
     * tanggal turun yang tidak terlihat di formulir tidak pernah ikut tersimpan (tinjauan
     * Tugas 3 M8). Pulang hari tidak punya tanggal turun.
     */
    private function selesai(?Carbon $tanggal, ?Carbon $pilihan, ?TripType $jenis): ?Carbon
    {
        if ($tanggal === null) {
            return null;
        }

        $batas = $tanggal->copy()->addDays(self::batasIsian()['rentangHari']);
        $isianTerlihat = $jenis !== null && $jenis->lamaHari() > 2;

        if ($isianTerlihat && $pilihan !== null && $pilihan->gte($tanggal) && $pilihan->lte($batas)) {
            return $pilihan->isSameDay($tanggal) ? null : $pilihan;
        }

        return $jenis !== null && $jenis->lamaHari() > 1 ? $tanggal->copy()->addDays($jenis->lamaHari() - 1) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function jalur(Trail $trail): array
    {
        $status = $this->statusJalur->untuk(EloquentCollection::make([$trail]))[$trail->id];

        return [
            'id' => $trail->id,
            'nama' => $trail->name,
            'gunung' => $trail->mountain->name,
            'url' => route('trails.show', $trail),
            'ringkas' => TrailFigures::ringkas($trail),
            'status' => $status,
            'peringatan' => $status['status'] === OfficialStatusValue::CLOSED->value
                ? 'Jalur ini sedang Tutup. Anda tetap dapat merencanakan tanggal mendatang; periksa statusnya lagi sebelum berangkat.'
                : null,
            'ganti' => route('trips.create'),
        ];
    }

    /**
     * Simpanan pendaki yang masih terbit, sebagai pilihan jalur untuk direncanakan. Setiap jalur
     * punya gunung (kunci asing wajib), jadi namanya dibaca langsung, sama dengan kartu jalur.
     *
     * @return array<int, array{nama: string, gunung: string, href: string}>
     */
    private function pilihanJalur(User $user): array
    {
        return $this->simpanan->simpanan($user)
            ->filter(fn (TrailConsideration $s) => $s->trail?->terbit() === true)
            ->map(fn (TrailConsideration $s) => [
                'nama' => $s->trail->name,
                'gunung' => $s->trail->mountain->name,
                'href' => route('trips.create', ['trail' => $s->trail->id]),
            ])
            ->values()
            ->all();
    }

    /**
     * Kecocokan untuk rencana ini: tujuan pendaki (bila ada) dengan jenis dan tanggal yang
     * sedang dipilih. Tanpa jenis trip, kecocokan dasar.
     *
     * target_date tujuan hanya ditimpa ketika $tanggal punya nilai; tanpa tanggal yang
     * sedang dipilih di URL, target_date asli tujuan (bila ada) tetap dipakai dan tidak
     * ditimpa null (ruling W4-3e).
     *
     * Pendaki tanpa profil lengkap tidak diberi label (§91, W4-6d), tetapi syarat dasar tidak
     * memakai data pengguna, jadi tetap dihitung: rencana yang gagal syarat dasar disebut
     * begitu beserta sebabnya, bukan lolos tanpa peringatan lalu ditolak di Cek (M2 tinjauan
     * akhir W4).
     *
     * @return array{label: string|null, labelText: string, alasan: string, denganRencana: bool, layak: bool}
     */
    private function kecocokan(User $user, Trail $trail, ?HikingGoal $goal, ?TripType $jenis, ?Carbon $tanggal): array
    {
        $tujuan = null;

        if ($jenis !== null) {
            $overrides = ['trip_type' => $jenis];

            if ($tanggal !== null) {
                $overrides['target_date'] = $tanggal;
            }

            $tujuan = ($goal?->replicate() ?? new HikingGoal)->fill($overrides);
        }

        $fit = $this->trailFit->forTrails($user, EloquentCollection::make([$trail]), $tujuan)[$trail->id];

        if (! $user->hasCompletedProfile()) {
            return [
                'label' => null,
                'labelText' => RouteFitLabel::textFor(null),
                'alasan' => $fit->eligible ? 'Isi profil pendaki supaya kecocokan jalur ini dapat dinilai untuk rencana Anda.' : $fit->alasan,
                'denganRencana' => false,
                'layak' => $fit->eligible,
            ];
        }

        return [
            'label' => $fit->label?->value,
            'labelText' => RouteFitLabel::textFor($fit->label),
            'alasan' => $fit->alasan,
            'denganRencana' => $fit->denganRencana,
            'layak' => $fit->eligible,
        ];
    }

    /**
     * Rentang tanggal dan kalimat terbitnya prakiraan BMKG (R-013): sebelum jendela terbit,
     * halaman menyebut kapan prakiraan untuk tanggal itu muncul dan tidak menebak cuaca. Tanggal
     * terbitnya dari PreparationService::tanggalTerbit(), hitungan yang sama dengan butir cuaca.
     *
     * @return array{rentang: string, ringkas: string, lama: string, prakiraan: string}
     */
    private function jadwal(Carbon $tanggal, ?Carbon $selesai, ?Trail $trail): array
    {
        $terbit = PreparationService::tanggalTerbit($tanggal);
        $hariIni = Carbon::now($trail !== null ? Timezone::forTrail($trail) : Timezone::DEFAULT)->toDateString();
        $lama = $selesai === null ? 1 : (int) $tanggal->diffInDays($selesai) + 1;

        return [
            'rentang' => KepalaTrip::rentang($tanggal, $selesai),
            // Bentuk pendek untuk ringkasan di bar aksi, supaya tetap satu baris di 360 px.
            'ringkas' => $selesai === null
                ? $tanggal->translatedFormat('j M')
                : $tanggal->translatedFormat($tanggal->month === $selesai->month ? 'j' : 'j M').' sampai '.$selesai->translatedFormat('j M'),
            'lama' => $lama.' hari',
            'prakiraan' => $terbit->toDateString() > $hariIni
                ? 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('D, j M').' terbit mulai '.$terbit->translatedFormat('D, j M').'. Sebelum itu Memount tidak menebak cuacanya.'
                : 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('D, j M').' sudah dapat terbit. Prakiraannya tampil di cek kesiapan begitu tersedia dari BMKG.',
        ];
    }

    /**
     * Jendela pemesanan izin untuk tanggal yang dipilih, dengan tanggal buka dan tutupnya
     * (spec v3 bagian 6.8). Keadaan dan tanggalnya dari PermitService::bookingWindowFor(),
     * satu-satunya tempat aritmetika jendela; tautannya dari PermitService::pemesananDariJendela()
     * atas jendela yang sama, satu penentu ajakan memesan untuk Persiapan, Rencanakan, dan Cek
     * (M1; W4-FK N4: "hari ini" dibaca sekali untuk judul dan tautan): jendela yang tutup atau
     * belum dibuka tidak menautkan "Pesan di sistem resmi". Aturan tanpa tanggal buka tidak
     * pernah ditulis "sedang dibuka" (R-007, ruling W4-FE). Kuota hanya disebut sebagai aturan
     * tercatat, tidak pernah sebagai sisa kuota (§43).
     *
     * Kalimat penutup $penutup (PermitService::kalimatTautan()) dipilih MENURUT $dasar['tautan']
     * yang sama dengan yang dirender (ruling W5-G1, R-007): klaim "menautkan" hanya benar ketika
     * tautan itu sungguh ada, jadi jendela BELUM_DIBUKA (tautan selalu null) tidak pernah
     * mendapat klaim itu.
     *
     * @return array{keadaan: string, judul: string, keterangan: string, tautan: array{href: string, teks: string, label: string}|null, kuota: string}|null
     */
    private function izin(Trail $trail, ?Carbon $tanggal): ?array
    {
        $aturan = $this->permits->requirementFor($trail);

        if ($aturan === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;
        $rentangH = match (true) {
            $buka !== null && $tutup !== null => "H-{$buka} sampai H-{$tutup}",
            $tutup !== null => "ditutup H-{$tutup}",
            $buka !== null => "dibuka H-{$buka}",
            default => null,
        };
        $kurung = $rentangH !== null ? ' ('.$rentangH.')' : '';
        $kuota = self::kuota($aturan);

        if ($buka === null && $tutup === null) {
            return ['keadaan' => 'tanpaJendela', 'judul' => 'Jalur ini mewajibkan izin', 'keterangan' => 'Jendela pemesanannya belum tercatat di Memount. Periksa ke '.$aturan->authority.'.', 'tautan' => PermitService::tautanPeriksa($aturan), 'kuota' => $kuota];
        }

        if ($tanggal === null) {
            return ['keadaan' => 'tanpaTanggal', 'judul' => 'Jalur ini mewajibkan izin', 'keterangan' => 'Pilih tanggal naik untuk melihat kapan pemesanannya dibuka dan ditutup. Dikelola '.$aturan->authority.$kurung.'.', 'tautan' => null, 'kuota' => $kuota];
        }

        $jendela = $this->permits->bookingWindowFor($trail, $tanggal, $aturan);

        // tanggalSah() dan bookingWindowFor() membaca "hari ini" terpisah. Bila tengah malam lewat
        // di antaranya, jendelanya tidak ada, dan tidak ada keadaan yang boleh ditulis (R-007).
        if ($jendela === null) {
            return null;
        }

        $tanggalBuka = $jendela['buka'] !== null ? PermitService::hari($jendela['buka']) : null;
        $tanggalTutup = $jendela['tutup'] !== null ? PermitService::hari($jendela['tutup']) : null;
        // Tautan dari jendela yang sama dengan judulnya: "hari ini" dibaca sekali (W4-FK N4).
        $dasar = ['tautan' => PermitService::pemesananDariJendela($jendela, $aturan)['tautan'], 'kuota' => $kuota];
        $penutup = ' '.PermitService::kalimatTautan($dasar['tautan'] !== null);

        return match ($jendela['state']) {
            PermitService::BOOKING_DITUTUP => [
                'keadaan' => 'tutup',
                'judul' => 'Pemesanan izin sudah tutup, hari terakhirnya '.$tanggalTutup,
                'keterangan' => 'Jendela pemesanan '.$aturan->authority.' untuk tanggal ini sudah lewat'.$kurung.'. Pilih tanggal lain atau tanyakan langsung ke pengelola.',
            ] + $dasar,
            PermitService::BOOKING_BELUM_DIBUKA => [
                'keadaan' => 'belum',
                'judul' => 'Pemesanan izin dibuka mulai '.$tanggalBuka,
                'keterangan' => 'Dibuka di sistem resmi '.$aturan->authority.$kurung.'.'.$penutup,
            ] + $dasar,
            PermitService::BOOKING_BUKA_BELUM_TERCATAT => [
                'keadaan' => 'bukaBelumTercatat',
                'judul' => 'Tanggal buka pemesanan izin belum tercatat',
                'keterangan' => ($tanggalTutup !== null ? 'Hari terakhir pemesanan '.$tanggalTutup.'. ' : '').'Periksa jendelanya di sistem resmi '.$aturan->authority.$kurung.'.'.$penutup,
            ] + $dasar,
            PermitService::BOOKING_TERBUKA => [
                'keadaan' => 'terbuka',
                'judul' => $tanggalTutup !== null ? 'Pemesanan izin terbuka sampai '.$tanggalTutup : 'Pemesanan izin sedang terbuka',
                'keterangan' => 'Dibuka sejak '.$tanggalBuka.' di sistem resmi '.$aturan->authority.$kurung.'.'.$penutup,
            ] + $dasar,
        };
    }

    private static function kuota(PermitRequirement $aturan): string
    {
        if ($aturan->daily_quota === null) {
            return 'Kuota harian jalur ini belum ada sumber terverifikasi. Pastikan saat memesan izin.';
        }

        $jumlah = number_format($aturan->daily_quota, 0, ',', '.');

        return $aturan->verified_at !== null
            ? 'Kuota harian tercatat '.$jumlah.' pendaki. Sisa kuota hanya ada di sistem resmi.'
            : 'Kuota harian tercatat '.$jumlah.' pendaki, belum terverifikasi. Pastikan saat memesan izin.';
    }
}
