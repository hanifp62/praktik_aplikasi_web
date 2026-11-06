<?php

namespace App\Services\Dashboard;

use App\Enums\CompletionState;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\Trails\SkalaProfil;
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\PendakianAktif;
use App\Services\Trips\TripReminderScheduler;
use App\Services\Trips\TripSteps;
use App\Support\Durasi;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45, rencana induk W5 butir 1).
 *
 * Fase dipilih menurut urutan kepentingan, bukan urutan alur: pendakian yang sedang berjalan
 * (B3), trip mendatang (B2), pendakian selesai yang belum dilaporkan (B4), profil yang belum
 * diisi (B0), lalu ajakan merencanakan (B1). Setiap fase membawa tepat satu aksi utama yang
 * dipilih di sini, bukan di klien. Konteksnya dihitung sekali per permintaan dan dipakai ulang
 * bagian Beranda lain lewat konteks(). Tanggal dan hitung mundur diformat dalam zona gunungnya.
 *
 * Trip yang tanggalnya lewat tanpa dimulai (W4-1c) tidak pernah menjadi trip mendatang. Fase
 * B0, B1, dan B4 menyebut yang paling baru dengan tautan ke ruang kerjanya, tempat penanda
 * "Tanggal rencana sudah lewat" dan aksi "Ubah rencana" berada.
 *
 * Status trip tidak pernah ditulis untuk trip yang belum dimulai (W4-R4, ruling W4-C4): kata
 * "Siap berangkat" di samping trip mendatang terbaca sebagai jaminan (R-014).
 */
final class RingkasanDasbor
{
    public const B0 = 'B0';

    public const B1 = 'B1';

    public const B2 = 'B2';

    public const B3 = 'B3';

    public const B4 = 'B4';

    /** Trip yang diperiksa untuk menemukan yang belum lewat di zona gunungnya. */
    private const KANDIDAT = 3;

    /** Butir kritis yang belum dikonfirmasi yang disebut namanya di kartu trip. */
    private const BUTIR_DISEBUT = 3;

    private ?KonteksBeranda $konteks = null;

    public function __construct(
        private readonly PendakianAktif $pendakianAktif,
        private readonly KepalaTrip $kepala,
        private readonly TripSteps $langkah,
        private readonly OfficialStatusService $status,
        private readonly PermitService $izin,
        private readonly TripReminderScheduler $pengingat,
    ) {}

    public function konteks(User $user): KonteksBeranda
    {
        if ($this->konteks !== null && $this->konteks->pengguna->is($user)) {
            return $this->konteks;
        }

        $profil = $user->hasCompletedProfile();
        $pendakian = $this->pendakianAktif->untuk($user);

        if ($pendakian !== null) {
            return $this->konteks = new KonteksBeranda(self::B3, $user, $profil, pendakian: $pendakian);
        }

        $trip = $this->tripMendatang($user);

        if ($trip !== null) {
            return $this->konteks = new KonteksBeranda(self::B2, $user, $profil, trip: $trip, aturanIzin: $this->izin->requirementFor($trip->trail));
        }

        $riwayat = $this->riwayatBelumDilaporkan($user);

        $fase = match (true) {
            $riwayat !== null => self::B4,
            ! $profil => self::B0,
            default => self::B1,
        };

        return $this->konteks = new KonteksBeranda($fase, $user, $profil, riwayat: $riwayat, tripLewat: $this->tripLewat($user));
    }

    /**
     * @return array{kunci: string, ringkasan: string, kartu: array<string, mixed>, tripLewat: array{nama: string, tanggal: string, url: string}|null}
     */
    public function props(User $user): array
    {
        $k = $this->konteks($user);

        return [
            'kunci' => $k->fase,
            'ringkasan' => $this->ringkasan($k),
            'kartu' => match ($k->fase) {
                self::B0 => ['jenis' => 'profil', 'aksi' => self::tautan('Isi profil pendaki', route('onboarding'))],
                self::B1 => ['jenis' => 'mulai', 'aksi' => self::tautan('Rencanakan trip', route('trips.create'))],
                self::B2 => $this->kartuPerjalanan($k->trip, $k->aturanIzin),
                self::B3 => $this->kartuMendaki($k->pendakian),
                default => $this->kartuLapor($k->riwayat),
            },
            'tripLewat' => $k->tripLewat === null ? null : [
                'nama' => $k->tripLewat->name,
                'tanggal' => KepalaTrip::rentang($k->tripLewat->planned_date, $k->tripLewat->end_date),
                'url' => route('trips.show', $k->tripLewat),
            ],
        ];
    }

    /**
     * Trip terdekat yang belum dimulai dan tanggalnya belum lewat di zona gunungnya. Lantai
     * kueri WIB (tanggal Indonesia yang paling akhir berganti hari), lalu beberapa kandidat
     * diperiksa di zona gunungnya masing-masing, supaya trip WIT yang hari ini sudah lewat
     * di Papua tidak terbaca mendatang selama WIB masih di tanggal yang sama.
     *
     * Pengingatnya hanya yang masih akan datang (TripReminder::akanDatang(), ruling W5-FE),
     * predikat yang sama dengan lembar Pengingat; satu-satunya pembacanya Tenggat terdekat.
     */
    private function tripMendatang(User $user): ?TripPlan
    {
        return $this->belumDimulai($user)
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->orderBy('planned_date')
            ->orderBy('id')
            ->limit(self::KANDIDAT)
            ->with([
                'trail.mountain',
                'preparationItems',
                'latestReadinessCheck',
                'latestConfirmedReadinessCheck',
                'reminders' => fn ($query) => $query->akanDatang(),
            ])
            ->get()
            ->first(fn (TripPlan $trip) => KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)) !== null);
    }

    /**
     * Rencana terbaru yang tanggalnya sudah lewat di zona gunungnya tanpa pernah dimulai.
     */
    private function tripLewat(User $user): ?TripPlan
    {
        return $this->belumDimulai($user)
            ->whereDate('planned_date', '<', Timezone::latestDateInIndonesia())
            ->orderByDesc('planned_date')
            ->orderByDesc('id')
            ->limit(self::KANDIDAT)
            ->with('trail.mountain')
            ->get()
            ->first(fn (TripPlan $trip) => KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)) === null);
    }

    /**
     * @return Builder<TripPlan>
     */
    private function belumDimulai(User $user): Builder
    {
        return TripPlan::query()
            ->whereBelongsTo($user)
            ->whereIn('status', self::statusBelumDimulai());
    }

    /**
     * Nilai status "belum dimulai" untuk kueri SQL, diturunkan dari TripStatus::belumDimulai()
     * (ruling W5-P4) dan bukan diketik ulang di sini: status baru yang ditandai belum dimulai
     * pada enumnya otomatis ikut tanpa menyunting kelas ini.
     *
     * @return array<int, string>
     */
    private static function statusBelumDimulai(): array
    {
        return array_map(
            fn (TripStatus $status) => $status->value,
            array_filter(TripStatus::cases(), fn (TripStatus $status) => $status->belumDimulai()),
        );
    }

    /**
     * Pendakian selesai paling lama hiking.beranda.hari_ajakan_lapor hari lalu (fase B4) yang
     * belum dilaporkan: tidak ada laporan yang ditautkan ke riwayatnya, dan pendakinya belum
     * menulis laporan untuk jalur itu sesudah pendakian selesai lewat jalan lain.
     */
    private function riwayatBelumDilaporkan(User $user): ?HikingHistory
    {
        return HikingHistory::query()
            ->where('user_id', $user->id)
            ->whereNotNull('trip_plan_id')
            ->whereNull('trail_condition_report_id')
            ->where('completed_at', '>=', Carbon::now()->subDays((int) config('hiking.beranda.hari_ajakan_lapor')))
            ->whereNotExists(fn ($laporan) => $laporan->select(DB::raw(1))
                ->from('trail_condition_reports')
                ->whereColumn('trail_condition_reports.user_id', 'hiking_history.user_id')
                ->whereColumn('trail_condition_reports.trail_id', 'hiking_history.trail_id')
                ->whereColumn('trail_condition_reports.created_at', '>=', 'hiking_history.completed_at'))
            ->with('tripPlan.trail.mountain')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }

    private function ringkasan(KonteksBeranda $k): string
    {
        return match ($k->fase) {
            self::B0 => 'Isi profil pendaki supaya jalur dinilai menurut pengalaman Anda.',
            self::B1 => 'Belum ada trip mendatang.',
            self::B2 => $this->ringkasanPerjalanan($k->trip),
            self::B3 => 'Anda sedang mendaki '.$k->pendakian['jalur'].'.',
            default => self::ringkasanRiwayat($k->riwayat),
        };
    }

    /**
     * Ringkasan B4 searah dengan hasil di kartu lapor (ruling W5-FK, M10 tinjauan akhir W5):
     * "sudah selesai" hanya untuk pendakian yang selesai sampai target; yang selesai sebagian
     * atau dibatalkan di jalur "sudah berakhir", supaya ringkasannya tidak mengaku selesai
     * sementara kartunya menulis "Dibatalkan di jalur".
     */
    private static function ringkasanRiwayat(HikingHistory $riwayat): string
    {
        return sprintf(
            'Pendakian %s sudah %s.',
            $riwayat->tripPlan->name,
            $riwayat->completion_state === CompletionState::COMPLETED ? 'selesai' : 'berakhir',
        );
    }

    private function ringkasanPerjalanan(TripPlan $trip): string
    {
        $kapan = match ($hari = self::hariLagi($trip)) {
            0 => $trip->name.' berangkat hari ini.',
            1 => $trip->name.' berangkat besok.',
            default => $trip->name.' tinggal '.$hari.' hari lagi.',
        };
        $belum = count(PreparationService::kritis($trip->preparationItems)['belum']);

        if ($belum === 0) {
            return $kapan;
        }

        // Ruling W5-2a: bila seluruh butir kritis yang belum dikonfirmasi sedang menunggu
        // BMKG, ringkasan menyebut itu dengan kalimat yang sama dengan
        // TripSteps::semuaMenungguBmkg, bukan "belum dikonfirmasi" yang terbaca sebagai
        // pekerjaan yang dapat dikerjakan sekarang.
        $menunggu = self::kritisMenungguBmkg($trip);

        if ($menunggu['jumlah'] === $belum) {
            return $kapan.' '.$belum.' butir cuaca kritis menunggu BMKG, terbit mulai '.$menunggu['terbit']->translatedFormat('D, j M').'.';
        }

        return $kapan.' '.$belum.' butir kritis belum dikonfirmasi.';
    }

    /**
     * Kartu perjalanan berikutnya (prototype 01): kepala trip yang sama dengan ruang kerja
     * (KepalaTrip), empat langkah dari TripSteps, persiapan kritis sebagai hitungan (R-005),
     * dan satu aksi utama.
     *
     * @return array<string, mixed>
     */
    private function kartuPerjalanan(TripPlan $trip, ?PermitRequirement $aturan): array
    {
        $statusSekarang = $this->status->effectiveStatusesForTrails(new EloquentCollection([$trip->trail]))[$trip->trail_id] ?? null;
        $kepala = $this->kepala->untuk($trip);
        $langkah = $this->langkah->for($trip, $statusSekarang);
        $kritis = PreparationService::kritis($trip->preparationItems);
        $menunggu = self::kritisMenungguBmkg($trip);
        $jalur = $trip->trail;

        return [
            'jenis' => 'perjalanan',
            'id' => $trip->id,
            'nama' => $trip->name,
            'url' => route('trips.show', $trip),
            'jalur' => $kepala['jalur'],
            'gunung' => $kepala['gunung'],
            'jenisTrip' => $kepala['jenis'],
            'hari' => $kepala['hari'],
            'tanggal' => $kepala['tanggal'],
            'jam' => $kepala['jam'],
            'peserta' => $kepala['peserta'],
            // Format yang sama dengan TrailRowResource ("-" bila tidak ada), supaya AngkaRingkas
            // yang membuang angka kosong dapat dipakai ulang.
            'angka' => [
                'jarak' => $jalur->distance_km !== null ? number_format((float) $jalur->distance_km, 1, ',', '.').' km' : '-',
                'tanjakan' => $jalur->elevation_gain_m !== null ? number_format($jalur->elevation_gain_m, 0, ',', '.').' m' : '-',
                'durasi' => Durasi::pendek($jalur->estimated_duration_minutes),
            ],
            'garis' => SkalaProfil::naikBersama([$jalur->id => $jalur->elevation_profile])['garis'][$jalur->id] ?? null,
            'langkah' => array_map(fn (array $l) => ['kunci' => $l['kunci'], 'judul' => $l['judul'], 'keadaan' => $l['keadaan']], $langkah),
            'peringatan' => $this->peringatan(collect($langkah)->firstWhere('kunci', 'kesiapan'), $statusSekarang),
            'persiapan' => [
                // Jumlah mentah butir kritis, terlepas statusnya, supaya kartu membedakan
                // "daftar belum dibuat" dari "seluruh butir kritis ditandai Tidak berlaku"
                // (ruling W5-2c / M3, pola yang sama dengan TripSteps::teksPersiapan).
                'jumlah' => $trip->preparationItems->where('is_critical', true)->count(),
                'dikonfirmasi' => $kritis['dikonfirmasi'],
                'berlaku' => $kritis['berlaku'],
                'belum' => array_slice($kritis['belum'], 0, self::BUTIR_DISEBUT),
                'sisa' => max(0, count($kritis['belum']) - self::BUTIR_DISEBUT),
                'menungguBmkg' => $menunggu['jumlah'] > 0
                    ? sprintf('%d butir cuaca menunggu BMKG, terbit mulai %s', $menunggu['jumlah'], $menunggu['terbit']->translatedFormat('D, j M'))
                    : null,
            ],
            'aksi' => $this->aksiPerjalanan($trip, $aturan, $kritis),
        ];
    }

    /**
     * Vonis kesiapan yang basi atau jalur yang ditarik (dari langkah Cek, TripSteps), atau
     * status resmi Tutup dan Dibatasi yang berlaku sekarang. Status lengkap dengan sumbernya
     * ada di cek kesiapan; di sini hanya peringatan yang mengubah rencana.
     *
     * @param  array{keterangan: string, peringatan: bool}  $kesiapan
     */
    private function peringatan(array $kesiapan, ?OfficialStatusValue $status): ?string
    {
        if ($kesiapan['peringatan']) {
            return $kesiapan['keterangan'];
        }

        return in_array($status, [OfficialStatusValue::CLOSED, OfficialStatusValue::RESTRICTED], true)
            ? 'Status resmi jalur sekarang: '.$status->label().'. Sumber dan waktunya ada di cek kesiapan.'
            : null;
    }

    /**
     * Satu aksi utama fase B2 (spec v3 bagian 6.1), dipilih menurut urutan ini:
     * - hari H: "Mulai pendakian" lewat tab Mendaki, tempat memulai dikonfirmasi;
     * - H-3 sampai H-1 (jendela prakiraan BMKG): "Cek kesiapan";
     * - izin wajib belum dikonfirmasi, pemesanan terbuka, dan tautan resminya tercatat: "Pesan
     *   di sistem resmi" (tautan keluar; Memount tidak menjual izin);
     * - pemesanan belum dibuka dan pengingat trip belum menyala: "Ingatkan saat dibuka", yang
     *   menyalakan pengingat trip termasuk hari pemesanan dibuka;
     * - ada butir kritis belum dikonfirmasi yang dapat dikerjakan sekarang: "Lanjutkan
     *   persiapan";
     * - selain itu: "Buka ruang kerja".
     *
     * Tanggal buka pemesanan yang belum tercatat (PermitService::BOOKING_BUKA_BELUM_TERCATAT,
     * ruling W5-P8) tidak cocok dengan kedua state eksplisit di bawah, jadi ia jatuh ke aturan
     * persiapan/ruang kerja seperti jalur tanpa aturan izin: belum diketahui apakah
     * pemesanannya sudah dibuka, jadi tidak ditulis "Pesan" atau "Ingatkan" (R-007).
     *
     * Ruling W5-2a (I1): "Lanjutkan persiapan" tidak pernah menunjuk ke butir yang server
     * tolak dikonfirmasi. Bila satu-satunya butir kritis yang tersisa menunggu BMKG
     * (PreparationService::menungguBmkg(), predikat sama dengan TripSteps, bukan salinan
     * baru), aksi jatuh ke aturan berikutnya: di luar H-3 sampai H-1 itu selalu "Buka ruang
     * kerja", karena bilah langkah (TripSteps) sudah menunjuk Cek sebagai berikutnya dalam
     * kasus ini, bukan Persiapan: aksi dan bilah tidak pernah berbeda jawaban.
     *
     * @param  array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}  $kritis
     * @return array{jenis: string, label: string, href: string, keterangan: string|null}
     */
    private function aksiPerjalanan(TripPlan $trip, ?PermitRequirement $aturan, array $kritis): array
    {
        $hari = self::hariLagi($trip);

        if ($hari === 0) {
            return self::tautan('Mulai pendakian', route('track'), 'Pendakian dimulai dari tab Mendaki sesudah Anda memastikannya.');
        }

        if ($hari <= (int) config('hiking.conditions.weather_forecast_days')) {
            return self::tautan('Cek kesiapan', route('trips.readiness', $trip));
        }

        if ($aturan !== null && ! PreparationService::izinDiurus($trip)) {
            $jendela = $this->izin->bookingWindowFor($trip->trail, $trip->planned_date, $aturan);
            $keadaan = $jendela['state'] ?? null;

            // Ruling W5-2c / M7: tautan pemesanan dibaca lewat penentu satu sumber
            // PermitService::pemesananDariJendela (M1 tinjauan akhir W4, filled() bukan
            // truthy), bukan pemeriksaan ad-hoc atas $aturan->booking_url di sini.
            if ($keadaan === PermitService::BOOKING_TERBUKA) {
                $tautan = PermitService::pemesananDariJendela($jendela, $aturan)['tautan'];

                if ($tautan !== null) {
                    return ['jenis' => 'luar', 'label' => 'Pesan di sistem resmi', 'href' => $tautan['href'], 'keterangan' => 'Sistem resmi '.$aturan->authority.'. Memount menautkan sistem resmi dan tidak menjual izin.'];
                }
            }

            $buka = $this->pengingat->bukaIzin($trip, $aturan);

            if ($keadaan === PermitService::BOOKING_BELUM_DIBUKA && ! $trip->reminders_enabled && $this->pengingat->dapatDiatur($trip) && $buka?->isFuture()) {
                return ['jenis' => 'pengingat', 'label' => 'Ingatkan saat dibuka', 'href' => route('trips.reminders.update', $trip), 'keterangan' => 'Menyalakan pengingat surel trip ini, termasuk pada hari pemesanan izin dibuka. Jadwalnya tampil di ruang kerja trip.'];
            }
        }

        if ($kritis['berlaku'] === 0 || self::kritisMenungguBmkg($trip)['dapatDikerjakan']) {
            return self::tautan('Lanjutkan persiapan', route('trips.preparation', $trip));
        }

        return self::tautan('Buka ruang kerja', route('trips.show', $trip));
    }

    /**
     * Butir kritis yang menunggu BMKG, dan apakah masih ada butir kritis lain yang belum
     * dikonfirmasi dan dapat dikerjakan sekarang (ruling W5-2a, I1). Predikat sama dengan
     * TripSteps (PreparationService::menungguBmkg()), diterapkan pada butir kritis saja
     * karena bagian ini hanya membaca persiapan kritis (R-005). Dipakai aksi, ringkasan, dan
     * kartu B2 supaya ketiganya tidak pernah menjawab berbeda soal butir yang belum dapat
     * dikerjakan.
     *
     * @return array{jumlah: int, terbit: ?Carbon, dapatDikerjakan: bool}
     */
    private static function kritisMenungguBmkg(TripPlan $trip): array
    {
        $terbit = PreparationService::terbitPrakiraan($trip);
        $kritisBelum = $trip->preparationItems
            ->where('is_critical', true)
            ->where('status', PreparationStatus::NOT_CONFIRMED);

        return [
            'jumlah' => $kritisBelum->filter(fn (TripPreparationItem $item) => PreparationService::menungguBmkg($item, $terbit))->count(),
            'terbit' => $terbit,
            'dapatDikerjakan' => $kritisBelum->contains(fn (TripPreparationItem $item) => ! PreparationService::menungguBmkg($item, $terbit)),
        ];
    }

    /**
     * Nama jalurnya sudah disebut ringkasan fase, jadi kartunya dibuka dengan pos berikutnya.
     *
     * @param  array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}  $pendakian
     * @return array<string, mixed>
     */
    private function kartuMendaki(array $pendakian): array
    {
        return [
            'jenis' => 'mendaki',
            'posBerikutnya' => $pendakian['posBerikutnya'],
            'aksi' => self::tautan('Buka mode lapangan', $pendakian['url']),
        ];
    }

    /**
     * Ringkasan singkat pendakian yang baru selesai dan ajakan melapor (fase B4). Tautan
     * laporan membawa trip, sehingga laporannya ditautkan ke riwayat ini dan fase B4 selesai.
     *
     * @return array<string, mixed>
     */
    private function kartuLapor(HikingHistory $riwayat): array
    {
        $trip = $riwayat->tripPlan;

        return [
            'jenis' => 'lapor',
            'nama' => $trip->name,
            'jalur' => $trip->trail->name,
            'gunung' => $trip->trail->mountain?->name,
            'hasil' => $riwayat->completion_state->label(),
            'selesai' => $riwayat->completed_at->copy()->setTimezone(Timezone::forTrail($trip->trail))->translatedFormat('D, j M Y'),
            'ringkasanUrl' => route('history.summary', $trip),
            'aksi' => self::tautan('Bagikan laporan kondisi', route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id])),
        ];
    }

    /**
     * @return array{jenis: string, label: string, href: string, keterangan: string|null}
     */
    private static function tautan(string $label, string $href, ?string $keterangan = null): array
    {
        return ['jenis' => 'tautan', 'label' => $label, 'href' => $href, 'keterangan' => $keterangan];
    }

    /**
     * Sisa hari ke tanggal mulai, dihitung dari tanggal hari ini di zona gunungnya; rumus yang
     * sama dengan KepalaTrip::hariMenuju(), dalam angka. Ruling W5-2c / M8: dibaca langsung
     * dari Timezone::hariSampai(), bukan salinan aritmetikanya.
     */
    private static function hariLagi(TripPlan $trip): int
    {
        return Timezone::hariSampai($trip->planned_date, Timezone::forTrail($trip->trail));
    }
}
