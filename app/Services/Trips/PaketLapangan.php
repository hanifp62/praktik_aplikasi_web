<?php

namespace App\Services\Trips;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Models\RestrictedArea;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\OfficialStatusService;
use App\Services\PreparationService;
use App\Services\WeatherService;
use App\Support\PenjagaDataContoh;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Paket lapangan luring satu trip (spec v3 bagian 10.2 dan 10.3; rencana induk W6 butir 4 dan 6;
 * ruling W6-R3, W6-R8, dan W6-8a sampai W6-8f).
 *
 * Tiga bagian, masing-masing teks JSON yang di-hash dan dikirim apa adanya, sehingga perangkat
 * dapat membuktikan setiap bagian utuh sebelum menyebut paket "Siap luring":
 * - lapangan: data mode lapangan yang stabil (HalamanMendaki tanpa laporan dan tanpa keadaan
 *   sesi, yang disimpan perangkat sendiri);
 * - rencana: rencana pribadi pendaki, aturan resmi bertanggal cek (CetakTrip::rencanaDanAturan),
 *   dan daftar persiapan dengan hitungan yang sama dengan ruang kerja (TripSteps) dan urutan
 *   yang sama dengan halaman Persiapan (HalamanPersiapan::urutanTampil);
 * - dinamis: potret status resmi beserta pembatasan segmen dan area terbatas, cuaca setiap
 *   tanggal trip, dan laporan komunitas, masing-masing dengan waktu pengetahuannya.
 *
 * Versi paket adalah hash isi ketiga bagian ditambah hash atribusi (ruling W6-8i): tidak ada
 * waktu unduh, keadaan sesi, skema, atau host di dalamnya (URL mode lapangan relatif, ruling
 * W6-8l). Data trip di manifest sudah tercermin di bagian lapangan dan rencana. Sebagian isi
 * dinamis tetap bergantung pada waktu, jadi versi dapat berganti tanpa data baru (ruling W6-8d):
 * - jendela laporan tiga puluh hari bergeser setiap hari;
 * - catatan status yang lewat expires_at berhenti berlaku;
 * - tanggal pertama cuaca trip yang sedang berjalan maju setiap hari di zona gunung;
 * - kalimat cuaca menambah tanda basi begitu pengambilan BMKG terakhir berumur lebih dari 24 jam;
 * - tanggal yang belum terbit menjadi "belum tersedia di Memount" begitu masuk jangkauan terbit.
 * Manifest dan bagian diambil terpisah tanpa penanda versi; perangkat membandingkan hash setiap
 * bagian dengan manifest dan menangani yang tidak cocok (Tugas 10).
 *
 * Waktu pada label adalah waktu Memount terakhir mencocokkan data dengan sumbernya
 * (verified_at, tanggal analisis BMKG, tanggal mendaki), bukan waktu unduh (R-013). Tanggal
 * ditulis menurut zona gunung; jam ditulis dengan penanda zonanya. Hanya jalur terbit (R-008,
 * R-022); tanpa GPX komunitas, keluaran AI, skor, nama pelapor, atau rencana orang lain.
 */
final class PaketLapangan
{
    public const BAGIAN = ['lapangan', 'rencana', 'dinamis'];

    public function __construct(
        private readonly HalamanMendaki $mendaki,
        private readonly CetakTrip $cetak,
        private readonly OfficialStatusService $status,
        private readonly WeatherService $cuaca,
    ) {}

    /**
     * Paket hanya untuk trip yang masih berjalan atau akan datang di jalur yang terbit
     * (Trail::terbit(), satu predikat dengan halaman lain).
     */
    public function bolehDipaket(TripPlan $trip): bool
    {
        $trip->loadMissing('trail');

        return $trip->status->isActive() && $trip->trail->terbit();
    }

    /**
     * URL bagian relatif, supaya paket tidak bergantung pada skema dan host yang dilihat server
     * di balik proxy TLS. Atribusi berurutan tetap: sumber garis jalur (indeks 0, dipakai peta
     * luring Tugas 9), peta dasar, lalu cuaca. Versi = 16 heks pertama sha256 dari sha256 ketiga
     * bagian ditambah sha256 atribusi, dipisah "|" (ruling W6-8i): sumber garis yang berganti
     * mengganti versi walau ketiga bagian tetap, sehingga perangkat tidak menyimpan atribusi
     * basi (R-009).
     *
     * dataContoh menandai paket dari data contoh, untuk lencana di daftar paket shell luring
     * (pra-tinjau PT9-19 bagian a). Ia di luar rumus versi: nilainya juga ada di bagian lapangan,
     * yang sudah ikut di-hash.
     *
     * @return array{versi: string, trip: array{id: int, nama: string, jalur: string, gunung: string|null, tanggal: string}, bagian: array<int, array{nama: string, url: string, ukuran: int, sha256: string}>, ukuran: int, ambang: array{bukaJam: int}, atribusi: array<int, string>, dataContoh: bool}
     */
    public function manifest(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain', 'trail.dataSource');
        $bagian = [];

        foreach (self::BAGIAN as $nama) {
            $teks = $this->teksBagian($trip, $pengguna, $nama);
            $bagian[] = [
                'nama' => $nama,
                'url' => route('trips.pack.section', ['trip' => $trip, 'bagian' => $nama], absolute: false),
                'ukuran' => strlen($teks),
                'sha256' => hash('sha256', $teks),
            ];
        }

        $atribusi = [
            self::sumberGaris($trip->trail),
            'Peta dasar tidak dibawa paket.',
            'Prakiraan cuaca: BMKG.',
        ];

        return [
            'versi' => substr(hash('sha256', implode('|', [...array_column($bagian, 'sha256'), hash('sha256', implode("\n", $atribusi))])), 0, 16),
            'trip' => [
                'id' => $trip->id,
                'nama' => $trip->name,
                'jalur' => $trip->trail->name,
                'gunung' => $trip->trail->mountain?->name,
                'tanggal' => $trip->planned_date->toDateString(),
            ],
            'bagian' => $bagian,
            'ukuran' => array_sum(array_column($bagian, 'ukuran')),
            'ambang' => ['bukaJam' => (int) config('hiking.field_pack.open_status_stale_after_hours')],
            'atribusi' => $atribusi,
            'dataContoh' => PenjagaDataContoh::sedangDisajikan(),
        ];
    }

    /**
     * Teks JSON satu bagian; yang di-hash sama dengan yang dikirim.
     */
    public function teksBagian(TripPlan $trip, User $pengguna, string $nama): string
    {
        $isi = match ($nama) {
            'lapangan' => $this->lapangan($trip, $pengguna),
            'rencana' => $this->rencana($trip, $pengguna),
            'dinamis' => $this->dinamis($trip),
            default => throw new InvalidArgumentException("Bagian paket {$nama} tidak dikenal."),
        };

        return json_encode($isi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Sumber garis jalur, pos, dan angka dari data_sources jalur (pola "Angka, pos, dan garis
     * rute" di rincian jalur, TrailDetailPage), bukan klaim tetap bahwa datanya milik Memount.
     */
    private static function sumberGaris(Trail $jalur): string
    {
        $sumber = $jalur->dataSource;

        if ($sumber === null) {
            return 'Sumber garis jalur belum tercatat.';
        }

        return 'Garis jalur, pos, dan angka: '.$sumber->source_name
            .(filled($sumber->source_owner) ? ', '.$sumber->source_owner : '')
            .(filled($sumber->source_url) ? ' ('.$sumber->source_url.')' : '')
            .'.';
    }

    /**
     * Data mode lapangan tanpa laporan dan tanpa keadaan sesi. Keadaan sesi (mulai, jeda, pos
     * tercapai) disimpan perangkat dari mode lapangan daring, supaya versi paket tidak berubah
     * setiap pendaki tiba di pos.
     *
     * @return array<string, mixed>
     */
    private function lapangan(TripPlan $trip, User $pengguna): array
    {
        $data = $this->mendaki->untuk($trip, $pengguna);
        unset($data['laporan']);

        return [
            ...$data,
            'trip' => [...$data['trip'], 'berlangsung' => false, 'mulai' => null, 'jedaSejak' => null, 'jedaDetik' => 0],
            'tercapai' => 0,
            'rekamJejak' => false,
            'sesiId' => null,
            'sumber' => 'paket',
            'urls' => [...$data['urls'], 'jejak' => null],
            // Lencana Data contoh ikut ke shell luring, yang tidak menerima props bersama server.
            // Bukan bernama demo: nama itu milik prop bersama (ReminderSheetTest).
            'dataContoh' => PenjagaDataContoh::sedangDisajikan(),
        ];
    }

    /**
     * Rencana, aturan, dan persiapan. Hitungan persiapan dari TripSteps::hitungPersiapan(), sama
     * dengan ruang kerja: butir Tidak berlaku tidak ikut "berlaku", dan setiap butir menyebut
     * apakah ia berlaku. Hitungan, bukan persen (R-005). Butir dalam urutan tampil halaman
     * Persiapan (kategori, kritis, urutan templat, id), dari satu sumber
     * HalamanPersiapan::urutanTampil() (ruling W6-8j).
     *
     * @return array{rencana: array<string, string|null>, aturan: array<string, string|null>|null, persiapan: array{dikonfirmasi: int, berlaku: int, jumlah: int, butir: array<int, array{nama: string, kritis: bool, keadaan: string, berlaku: bool}>}}
     */
    private function rencana(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('preparationItems.preparationItem:id,sort_order');
        $butir = HalamanPersiapan::urutanTampil($trip->preparationItems);
        $rencanaDanAturan = $this->cetak->rencanaDanAturan($trip, $pengguna);

        return [
            'rencana' => $rencanaDanAturan['rencana'],
            'aturan' => $rencanaDanAturan['aturan'],
            'persiapan' => [
                ...TripSteps::hitungPersiapan($butir),
                'butir' => $butir->map(fn (TripPreparationItem $b) => [
                    'nama' => $b->label,
                    'kritis' => (bool) $b->is_critical,
                    'keadaan' => $b->status->label(),
                    'berlaku' => $b->status !== PreparationStatus::NOT_APPLICABLE,
                ])->all(),
            ],
        ];
    }

    /**
     * Potret data dinamis (spec v3 bagian 10.3), semuanya "terakhir diketahui" dengan waktunya.
     *
     * @return array<string, mixed>
     */
    private function dinamis(TripPlan $trip): array
    {
        $trip->loadMissing('trail.mountain');
        $jalur = $trip->trail;
        $zona = Timezone::forTrail($jalur);

        return [
            'status' => $this->statusResmi($jalur, $zona),
            'cuaca' => $this->cuacaTrip($trip, $zona),
            'laporan' => $this->laporan($jalur),
        ];
    }

    /**
     * Status resmi jalur dengan waktu ceknya, pembatasan segmen, dan area terbatas.
     *
     * Status Buka membawa waktu turunnya, `turunSejak` = min(verified_at + N jam, expires_at)
     * (Q9 dengan N dari hiking.field_pack.open_status_stale_after_hours; ruling W6-R8 dan
     * W6-8b): perangkat tidak menyebut Buka lebih lama daripada server sendiri menganggap
     * catatannya berlaku. Tutup dan Dibatasi tidak pernah turun; perangkat menulisnya dengan umur
     * cek dan masa berlakunya (W6-8e). Belum diketahui tetap Belum diketahui (R-006).
     *
     * Masa berlaku ditulis tanggal saja bila instan tersimpannya tepat 00.00 UTC, yaitu bentuk
     * isian tanggal admin dan seeder, atau tepat 00.00 di zona gunung; selain itu dengan jam dan
     * penanda zona (ruling W6-8k, tinjauan I-1). Jam yang tidak pernah dimasukkan sumber tidak
     * ditulis (R-005). berlakuIso dan turunSejak tetap memakai instan tersimpan. Sampai semantik
     * "sampai tanggal" diputus W8 (PW8-7.7), masa berlaku tanggal 31 Okt berakhir 00.00 UTC (07.00
     * WIB): sesudah itu perangkat menulis "Masa berlaku itu sudah lewat; pastikan di pos
     * registrasi." pada hari itu juga. Arahnya hati-hati, tanpa jam karangan.
     *
     * Pembatasan segmen (PRD §42) ikut menurut urutan rute lalu id (W6-8h), dan area terbatas
     * (PRD §65-66) menurut id (W6-8c), supaya hash versi sama di SQLite dan PostgreSQL. Keduanya
     * tidak pernah mengubah nilai status jalur (R-009, R-010).
     *
     * @return array<string, mixed>
     */
    private function statusResmi(Trail $jalur, string $zona): array
    {
        $potret = $this->status->snapshotForTrail($jalur);
        $nilai = OfficialStatusValue::from($potret['status']);
        $dicek = $potret['verified_at'] === null ? null : Carbon::parse($potret['verified_at'])->utc();
        $berlaku = $potret['expires_at'] === null ? null : Carbon::parse($potret['expires_at'])->utc();

        return [
            'nilai' => $nilai->value,
            'label' => $potret['status_label'],
            'sumber' => $potret['source'],
            'jenisSumber' => $potret['source_type'],
            'alasan' => $potret['reason'],
            'dicek' => $dicek?->toIso8601String(),
            'dicekTeks' => Timezone::display($dicek, $zona, 'j M Y H.i'),
            'berlaku' => $berlaku === null ? null : Timezone::display($berlaku, $zona, $berlaku->format('H:i:s') === '00:00:00' || $berlaku->copy()->setTimezone($zona)->format('H.i') === '00.00' ? 'j M Y' : 'j M Y H.i'),
            'berlakuIso' => $berlaku?->toIso8601String(),
            'turunSejak' => self::turunSejak($nilai, $dicek, $berlaku),
            'pembatasan' => array_map(fn (array $p) => [
                'segmenId' => $p['segment_id'],
                'segmen' => $p['segment'],
                'nilai' => $p['status']->value,
                'label' => $p['status']->label(),
                'alasan' => $p['reason'],
                'dicekTeks' => Timezone::display($p['verified_at'] === null ? null : Carbon::instance($p['verified_at']), $zona, 'j M Y H.i'),
            ], $this->status->pembatasanSegmenPaket($jalur)),
            'areaTerbatas' => $jalur->restrictedAreas()
                ->sortBy('id')
                ->map(fn (RestrictedArea $a) => ['id' => $a->id, 'nama' => $a->name, 'alasan' => $a->reason])
                ->values()
                ->all(),
        ];
    }

    /**
     * Waktu status Buka berhenti dapat dipercaya, ISO 8601 UTC; null selain Buka dan bila
     * tanggal ceknya tidak tercatat (perangkat langsung menulis "belum dapat dipastikan").
     */
    private static function turunSejak(OfficialStatusValue $nilai, ?Carbon $dicek, ?Carbon $berlaku): ?string
    {
        if ($nilai !== OfficialStatusValue::OPEN || $dicek === null) {
            return null;
        }

        $ambang = $dicek->copy()->addHours((int) config('hiking.field_pack.open_status_stale_after_hours'));

        return ($berlaku !== null && $berlaku->lessThan($ambang) ? $berlaku : $ambang)->copy()->utc()->toIso8601String();
    }

    /**
     * Prakiraan BMKG untuk setiap tanggal trip (ruling W6-8a): dari tanggal mulai atau hari ini
     * di zona gunung, mana yang lebih akhir, sampai tanggal selesai trip. Baris diambil dengan
     * satu kueri rentang forecast_at, bukan jendela "sekarang" WeatherService::forecastForTrail(),
     * lalu dikelompokkan menurut tanggal lokal gunung. Tidak pernah cuaca karangan (R-013).
     *
     * Kalimat setiap tanggal tanpa baris membedakan dua keadaan (preseden ruling W3-6c): tanggal
     * yang sudah dalam jangkauan terbit BMKG (PreparationService::tanggalTerbit) tetapi barisnya
     * belum ada di Memount, dan tanggal yang prakiraannya memang belum terbit. Tanggal berurutan
     * yang belum terbit dilebur satu entri.
     *
     * @return array{kalimat: string, hari: array<int, array{tanggal: string, label: string, kalimat: string|null, baris: array<int, array{waktu: string, cuaca: string, suhu: string}>}>}
     */
    private function cuacaTrip(TripPlan $trip, string $zona): array
    {
        $konteks = $this->cuaca->contextForTrail($trip->trail);

        if (! $konteks['available']) {
            return ['kalimat' => 'Data cuaca BMKG tidak tersedia saat paket dibuat.', 'hari' => []];
        }

        $hariIni = Carbon::now($zona)->toDateString();
        $mulai = max($trip->planned_date->toDateString(), $hariIni);
        $akhir = max($mulai, ($trip->end_date ?? $trip->planned_date)->toDateString());

        $semua = WeatherSnapshot::query()
            ->where('adm4_code', $trip->trail->weather_adm4_code)
            ->whereBetween('forecast_at', [
                Carbon::parse($mulai, $zona)->startOfDay()->utc(),
                Carbon::parse($akhir, $zona)->endOfDay()->utc(),
            ])
            ->orderBy('forecast_at')
            ->orderBy('id')
            ->get();
        $perTanggal = $semua->groupBy(fn (WeatherSnapshot $s) => $s->forecast_at->copy()->setTimezone($zona)->toDateString());

        $hari = [];
        $belumTerbit = [];

        for ($tanggal = Carbon::parse($mulai); $tanggal->toDateString() <= $akhir; $tanggal->addDay()) {
            $baris = $perTanggal->get($tanggal->toDateString());

            if ($baris === null && PreparationService::tanggalTerbit($tanggal)->toDateString() > $hariIni) {
                $belumTerbit[] = $tanggal->copy();

                continue;
            }

            $hari = [...$hari, ...self::leburBelumTerbit($belumTerbit)];
            $belumTerbit = [];
            $hari[] = [
                'tanggal' => $tanggal->toDateString(),
                'label' => $tanggal->translatedFormat('D, j M'),
                'kalimat' => $baris === null ? 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('j M').' belum tersedia di Memount saat paket dibuat.' : null,
                'baris' => $baris === null ? [] : $baris->map(fn (WeatherSnapshot $s) => [
                    'waktu' => $s->forecast_at->copy()->setTimezone($zona)->format('H.i').' '.Timezone::label($zona),
                    'cuaca' => $s->weather_description ?? 'Tidak dicatat',
                    'suhu' => $s->temperature_c === null ? 'Tidak dicatat' : number_format((float) $s->temperature_c, 0, ',', '.')."\u{00A0}°C",
                ])->values()->all(),
            ];
        }

        $hari = [...$hari, ...self::leburBelumTerbit($belumTerbit)];

        if ($semua->isEmpty()) {
            return ['kalimat' => $hari[0]['kalimat'], 'hari' => $hari];
        }

        $kalimat = (filled($konteks['reference_area']) ? 'Prakiraan area '.$konteks['reference_area'].' (BMKG), ' : 'Prakiraan BMKG (area referensi tidak tercatat), ')
            .self::waktuAnalisis($semua, $zona).'.';

        if (($konteks['freshness'] ?? null) === FreshnessState::STALE->value) {
            $kalimat .= ' Data cuaca terakhir diperbarui '.Timezone::display(Carbon::parse($konteks['fetched_at']), $zona, 'j M Y H.i').'.';
        }

        return ['kalimat' => $kalimat, 'hari' => $hari];
    }

    /**
     * Tanggal berurutan yang prakiraannya belum terbit, satu entri: satu tanggal menyebut kapan
     * prakiraannya mulai terbit, rentang menyebut jangkauan BMKG saja.
     *
     * @param  array<int, Carbon>  $tanggal
     * @return array<int, array{tanggal: string, label: string, kalimat: string, baris: array<int, never>}>
     */
    private static function leburBelumTerbit(array $tanggal): array
    {
        if ($tanggal === []) {
            return [];
        }

        $pertama = $tanggal[0];
        $terakhir = $tanggal[count($tanggal) - 1];
        $jangkauan = 'BMKG menerbitkan '.(int) config('hiking.conditions.weather_forecast_days').' hari ke depan';

        return [[
            'tanggal' => $pertama->toDateString(),
            'label' => count($tanggal) === 1
                ? $pertama->translatedFormat('D, j M')
                : $pertama->translatedFormat('D, j M').' sampai '.$terakhir->translatedFormat('D, j M'),
            'kalimat' => count($tanggal) === 1
                ? 'Prakiraan BMKG untuk '.$pertama->translatedFormat('j M').' belum terbit saat paket dibuat. '.$jangkauan.', mulai '.PreparationService::tanggalTerbit($pertama)->translatedFormat('j M').'.'
                : 'Prakiraan BMKG untuk '.$pertama->translatedFormat('j M').' sampai '.$terakhir->translatedFormat('j M').' belum terbit saat paket dibuat. '.$jangkauan.'.',
            'baris' => [],
        ]];
    }

    /**
     * Waktu pengetahuan prakiraan yang dibawa (pra-tinjau PT8-9): analisis BMKG tertua di antara
     * baris yang dibawa; bila tidak ada satu pun, waktu Memount mengambilnya; bila keduanya tidak
     * tercatat, kalimat itu yang ditulis, bukan waktu kosong. Cabang terakhir tidak tercapai selama
     * fetched_at NOT NULL (migrasi 2026_09_19_000310); dipertahankan atas ruling PT8-9.
     *
     * @param  Collection<int, WeatherSnapshot>  $baris
     */
    private static function waktuAnalisis(Collection $baris, string $zona): string
    {
        $analisis = $baris->pluck('analysis_date')->filter()->sort()->first();

        if ($analisis !== null) {
            return 'analisis '.Timezone::display($analisis, $zona, 'j M Y H.i');
        }

        $diambil = $baris->pluck('fetched_at')->filter()->sort()->first();

        return $diambil !== null
            ? 'diambil Memount '.Timezone::display($diambil, $zona, 'j M Y H.i')
            : 'waktu analisis tidak tercatat';
    }

    /**
     * Laporan komunitas yang lolos moderasi dalam jendela terbaru, paling banyak
     * hiking.conditions.community_report_limit (satu sumber dengan mode lapangan daring), terbaru
     * dulu dengan pemecah seri id, bertanggal mendaki (bukan "kemarin" yang basi esok hari), dan
     * tanpa nama pelapor (R-010).
     *
     * @return array<int, array{segmen: int|null, kondisi: string, catatan: string|null, waktu: string}>
     */
    private function laporan(Trail $jalur): array
    {
        return TrailConditionReport::query()
            ->where('trail_id', $jalur->id)
            ->visibleToPublic()
            ->dalamJendelaTerbaru()
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->limit((int) config('hiking.conditions.community_report_limit'))
            ->get(['id', 'trail_segment_id', 'condition_tags', 'note', 'hike_date'])
            ->map(fn (TrailConditionReport $l) => [
                'segmen' => $l->trail_segment_id,
                'kondisi' => implode(', ', array_map(fn ($tag) => $tag->label(), $l->tags())),
                'catatan' => $l->note,
                'waktu' => 'mendaki '.$l->hike_date->translatedFormat('j M'),
            ])
            ->all();
    }
}
