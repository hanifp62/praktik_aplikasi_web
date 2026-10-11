<?php

namespace App\Services\Trails;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\CheckpointPaceService;
use App\Services\ConditionAggregatorService;
use App\Services\ConsiderationService;
use App\Services\DataFreshnessService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\ProgressLadderService;
use App\Services\RouteFit\CompatibilityScorer;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFitService;
use App\Services\TrailFitService;
use App\Services\TrailNewsService;
use App\Support\Timezone;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Props halaman rinci jalur (FR-07, spec v3 bagian 6.4), sudah diformat di server: angka,
 * tantangan, kecocokan, profil elevasi, pos, kondisi, laporan, persiapan, dan sumber,
 * masing-masing dengan sumbernya sendiri (PRD §34, §60, §92).
 *
 * Waktu ditampilkan dalam zona gunungnya lengkap dengan penanda (§93). Skor internal,
 * bobot, dan probabilitas tidak pernah ikut (BR-09): kecocokan sampai ke klien sebagai
 * label, alasan, dan daftar kalimat; ketujuh faktor ada di halaman penilaian kecocokan.
 */
class TrailDetailPage
{
    public function __construct(
        private readonly ConditionAggregatorService $conditions,
        private readonly RouteFitService $routeFit,
        private readonly ProgressLadderService $progressLadder,
        private readonly PermitService $permits,
        private readonly CheckpointPaceService $paces,
        private readonly TrailNewsService $news,
        private readonly ConsiderationService $consideration,
        private readonly CompatibilityScorer $penilai,
        private readonly TrailFitService $ringkasFit,
        private readonly PreparationService $persiapan,
        private readonly PosSepanjangJalur $posKm,
        private readonly DataFreshnessService $kesegaran,
    ) {}

    /**
     * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang. Yang
     * ditampilkan keadaan menunggu beserta siapa yang ditunggu, bukan rincian setengah
     * jadi yang terbaca seperti rincian utuh.
     *
     * @return array<string, mixed>
     */
    public function menunggu(Trail $trail): array
    {
        $badan = $trail->mountain->responsibleAuthority();

        return [
            'menunggu' => $trail->awaitingData(),
            'badan' => $badan ? ['nama' => $badan->displayName(), 'website' => $badan->website] : null,
        ];
    }

    /**
     * Isi rincian jalur dalam urutan pengungkapan PRD §89 (spec v3 bagian 6.4): angka sekali
     * jalan naik, tantangan, kecocokan, profil elevasi, pos, kondisi sekarang, laporan
     * pendaki, yang perlu disiapkan, lalu sumber di halaman ini.
     *
     * Kecocokan dinilai "untuk rencana ini" bila pembaca punya trip terencana yang belum lewat
     * di jalur ini (trip terdekat), dengan tujuan trip itu; tanpa trip, kecocokan dasar tanpa
     * tujuan. Tujuan rekomendasi terakhir tidak dipakai: ia bukan rencana di jalur ini.
     *
     * @return array<string, mixed>
     */
    public function props(Trail $trail, User $user): array
    {
        $profilLengkap = $user->hasCompletedProfile();
        $zona = Timezone::forTrail($trail);
        // Trip terdekat dicari tanpa syarat profil: catatan prakiraan untuk tanggal trip (R-013)
        // tidak ada urusannya dengan profil; hanya penilaian kecocokan yang butuh (T6-M2).
        $trip = $this->tripTerdekat($user, $trail, $zona);
        // Izin diambil sekali dan dioper ke mesin; tanpa itu evaluate() mencarinya lagi.
        $izin = $this->permits->requirementFor($trail);
        $fit = $profilLengkap ? $this->routeFit->evaluate($user, $trip?->tujuanPenilaian(), $trail, permit: $izin) : null;

        $kondisi = $this->conditions->forTrail($trail);
        $laporan = $kondisi['community_context']['reports'] ?? [];
        $tersimpan = $this->consideration->simpanan($user);
        $simpananIni = $tersimpan->firstWhere('trail_id', $trail->id);
        $paces = $this->paces->forTrail($trail);
        $posKm = $this->posKm->km($trail);

        return [
            'kecocokan' => $fit === null ? null : $this->kecocokan($fit, $trail, $user, $trip),
            'ujung' => $this->ujung($trail),
            // Tiga angka sekali jalan naik. Turunan tidak ikut: elevation_loss_m di data pemilik
            // (MvpDatasetSeeder, DemoSeeder) sama dengan tanjakan, yaitu turunan pulang, jadi
            // menulisnya sebagai angka sekali jalan akan keliru (R-005; ruling W3, OPEN ISSUE).
            'angkaNaik' => [
                $this->angka('Jarak', TrailFigures::jarak($trail->distance_km)),
                $this->angka('Tanjakan', TrailFigures::meter($trail->elevation_gain_m)),
                $this->angka('Naik', TrailFigures::durasi($trail->estimated_duration_minutes)),
            ],
            'tantangan' => $this->tantangan($trail),
            'profilElevasi' => ProfilElevasiJalur::untuk($trail->elevation_profile, $trail->checkpoints, $posKm),
            // Bukan 'peta': nama itu milik props bersama (konfigurasi basemap).
            'petaJalur' => [
                'geometry' => $trail->readGeoJson('geometry'),
                'penanda' => $this->penandaPos($trail),
            ],
            'pos' => CheckpointJourney::daftar($trail->checkpoints, $paces, $posKm),
            'waktuDariRekaman' => CheckpointJourney::rekamanTerpakai($trail->checkpoints, $paces),
            'segmen' => $trail->segments->map(fn ($s) => [
                'id' => $s->id,
                'judul' => $s->sequence.'. '.$s->name,
                'ringkas' => implode(', ', [
                    $s->distance_km !== null ? number_format((float) $s->distance_km, 1, ',', '.').' km' : 'jarak belum tersedia',
                    $s->elevation_gain_m !== null ? 'naik '.number_format($s->elevation_gain_m, 0, ',', '.').' m' : 'tanjakan belum tersedia',
                    'teknis '.($s->technical_demand?->label() ?? 'belum tersedia'),
                ]),
                'description' => $s->description,
            ])->values()->all(),
            'statusResmi' => $this->statusResmi($kondisi['official_status'], $zona),
            'perizinan' => $this->perizinan($izin, $zona),
            'cuaca' => $this->cuaca($kondisi['weather_context']) + ['untukTrip' => $this->untukTrip($kondisi['weather_context'], $trip, $zona)],
            'komunitas' => $this->komunitas($kondisi['community_context'], $laporan, $user),
            'persiapan' => $this->persiapan($trail, $izin),
            'sumber' => $this->sumber($trail, $kondisi, $izin, $fit !== null, count($laporan), $zona),
            'mengikutiGunung' => $this->news->follows($user, $trail->mountain),
            'ditimbang' => $simpananIni !== null,
            'adaCatatan' => $simpananIni?->note !== null,
            'bandingUrl' => $this->bandingUrl($trail, $tersimpan),
        ];
    }

    /**
     * Trip terencana pembaca di jalur ini yang tanggalnya belum lewat di zona gunungnya,
     * yang terdekat lebih dulu.
     *
     * Himpunan status mendatang sama dengan yang dipakai pengingat perjalanan
     * (TripReminderScheduler::STATUS_LAYAK, DaftarPengingat): PLANNED dan
     * READY_FOR_DEPARTURE (ruling W3-6a). Tanpa READY_FOR_DEPARTURE, trip yang sudah
     * lolos PreDepartureCheck beberapa hari sebelum berangkat, yaitu saat penilaian
     * "untuk rencana ini" paling berguna, turun diam-diam ke kecocokan dasar.
     */
    private function tripTerdekat(User $user, Trail $trail, string $zona): ?TripPlan
    {
        return TripPlan::query()
            ->whereBelongsTo($user)
            ->where('trail_id', $trail->id)
            ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::READY_FOR_DEPARTURE->value])
            ->whereDate('planned_date', '>=', Carbon::now($zona)->toDateString())
            ->with('hikingGoal')
            ->orderBy('planned_date')
            ->first();
    }

    /**
     * "Cocokkah untuk saya?": label dan definisinya, satu alasan, lalu tiga daftar dari
     * penjelasan mesin yang sama dengan halaman rekomendasi (mengapa sesuai, yang perlu
     * diperhatikan, celah persiapan), tanpa skor atau bobot (BR-09, R-005).
     *
     * @return array<string, mixed>
     */
    private function kecocokan(RouteFitResult $fit, Trail $trail, User $user, ?TripPlan $trip): array
    {
        $alasan = $this->ringkasFit->alasan($fit);

        // Temuan tangkapan W3 butir 6 (ruling W3-G1 butir d): alasan() mengambil detail
        // faktor lemah pertama sebagai kalimat utama, dan what_to_watch menulis detail
        // faktor lemah yang sama lagi di urutan pertamanya. Disaring sebelum dipotong tiga,
        // supaya butir ketiga yang sah tidak hilang akibat penyaringan.
        $perluDiperhatikan = array_values(array_filter(
            $fit->explanation['what_to_watch'] ?? [],
            fn (string $baris): bool => $baris !== $alasan
        ));

        return [
            'label' => $fit->label?->value,
            'labelText' => RouteFitLabel::textFor($fit->label),
            'deskripsi' => $fit->label?->description(),
            'layak' => $fit->eligible,
            'rencana' => $trip?->ringkasan(),
            'alasan' => $alasan,
            // Paling banyak tiga per daftar; seluruh faktornya di halaman penilaian kecocokan.
            'mengapaSesuai' => $fit->eligible ? array_slice($fit->explanation['why_it_fits'] ?? [], 0, 3) : [],
            'perluDiperhatikan' => array_slice($perluDiperhatikan, 0, 3),
            'celahPersiapan' => array_slice($fit->explanation['preparation_gap'] ?? [], 0, 3),
            // Acuannya diri sendiri, bukan pendaki lain (R-015).
            'tangga' => $this->progressLadder->bandingkanDenganRiwayat($user, $trail),
            'penilaianUrl' => route('trails.fit', ['trail' => $trail->slug] + ($trip === null ? [] : ['trip' => $trip->id])),
        ];
    }

    /**
     * Pos pertama dan terakhir, untuk kalimat "dari ... ke ..." di atas angka sekali jalan.
     *
     * @return array{awal: string, akhir: string}|null
     */
    private function ujung(Trail $trail): ?array
    {
        $pos = $trail->checkpoints->sortBy('sequence')->values();

        return $pos->count() < 2 ? null : ['awal' => $pos->first()->name, 'akhir' => $pos->last()->name];
    }

    /**
     * "Apa tantangannya?" (spec v3 bagian 6.4). Tuntutan fisik, teknis, dan navigasi masing-
     * masing sebagai kata dan tiga ruas; yang belum diketahui tidak diberi ruas, bukan ruas
     * terendah (R-006). Medan, air, dan area kemah sebagai kata dari data jalur. Keadaan
     * lingkungan (suhu, angin) tidak ditulis karena belum ada datanya (R-007).
     *
     * @return array<int, array{judul: string, kata: string, ruas: int|null}>
     */
    private function tantangan(Trail $trail): array
    {
        $fisik = $this->penilai->physicalDemand($trail);
        $medan = array_map(fn ($t) => $t->label(), $trail->terrainTypes());

        return [
            [
                'judul' => 'Tuntutan fisik',
                'kata' => $fisik['known'] ? ['Ringan', 'Sedang', 'Berat', 'Sangat berat'][$fisik['rank'] - 1] : TrailFigures::BELUM,
                'ruas' => $fisik['known'] ? min($fisik['rank'], 3) : null,
            ],
            [
                'judul' => 'Tuntutan teknis',
                'kata' => $trail->technical_demand?->label() ?? TrailFigures::BELUM,
                'ruas' => $trail->technical_demand === null ? null : min($trail->technical_demand->rank(), 3),
            ],
            [
                'judul' => 'Navigasi',
                'kata' => $trail->navigation_complexity?->label() ?? TrailFigures::BELUM,
                // Peringkat dari enum (sama dengan tuntutan teknis), bukan salinan match di sini
                // (T6-M6 tinjauan akhir W3, bagian yang murah).
                'ruas' => $trail->navigation_complexity === null ? null : min($trail->navigation_complexity->rank(), 3),
            ],
            ['judul' => 'Medan', 'kata' => $medan === [] ? TrailFigures::BELUM : implode(', ', $medan), 'ruas' => null],
            ['judul' => 'Sumber air', 'kata' => $trail->water_availability?->label() ?? TrailFigures::BELUM, 'ruas' => null],
            ['judul' => 'Area kemah', 'kata' => $trail->camping_available ? 'Tersedia' : 'Tidak tersedia', 'ruas' => null],
        ];
    }

    /**
     * BMKG menerbitkan prakiraan beberapa hari ke depan (ruling W3-6b, R-013). Sebelum
     * jendela terbitnya, halaman hanya menyebut kapan prakiraan tanggal trip mulai muncul
     * (status "menunggu"), kalimat yang sama seperti sebelum putaran ini.
     *
     * Begitu jendela terbit tercapai, kalimat itu tidak lagi cukup: delapan baris pertama
     * kartu cuaca hanya mencakup sekitar 24 jam, sedangkan trip bisa dua atau tiga hari lagi.
     * Baris di bawah ini mencari entrinya sendiri di forecast PENUH (array_filter atas
     * $cuaca['forecast'] sebelum dipotong cuaca() ke delapan baris), disaring menurut
     * tanggal lokal gunungnya, bukan menurut indeks. Ketemu, statusnya "tersedia" dengan
     * judul dan baris prakiraan tanggal itu.
     *
     * Dua keadaan "tidak ketemu" dibedakan (ruling W3-6c), karena kalimatnya menyebut
     * "prakiraan yang tampil di bawah": sumber cuaca masih hidup tetapi datanya belum
     * mencapai tanggal trip (available true) mendapat status "tidakTercakup" dengan
     * kalimat itu, karena kartunya memang menggambar tabel prakiraan terdekat di bawahnya.
     * Sumber cuaca gagal total (available false) tidak punya tabel apa pun untuk dirujuk
     * kalimat itu, jadi hasilnya null; pesan galat kartu cuaca yang sudah ada menjadi
     * satu-satunya kabar (R-013), bukan kalimat yang menjanjikan baris yang tidak pernah
     * tampil.
     *
     * @param  array<string, mixed>  $cuaca
     * @return array{status: string, pesan?: string, judul?: string, forecast?: array<int, array<string, mixed>>}|null
     */
    private function untukTrip(array $cuaca, ?TripPlan $trip, string $zona): ?array
    {
        if ($trip === null) {
            return null;
        }

        $hari = (int) config('hiking.conditions.weather_forecast_days');
        $terbit = $trip->planned_date->copy()->subDays($hari);
        $tanggal = $trip->planned_date->translatedFormat('j M');

        if ($terbit->toDateString() > Carbon::now($zona)->toDateString()) {
            return [
                'status' => 'menunggu',
                'pesan' => 'Prakiraan untuk '.$tanggal.' belum terbit. BMKG menerbitkan '.$hari
                    .' hari ke depan; prakiraan tanggal Anda muncul di sini mulai '.$terbit->translatedFormat('j M').'.',
            ];
        }

        // Sumber cuaca gagal total (available false): tidak ada satu pun baris prakiraan
        // untuk disaring, dan kartu cuaca sendiri sudah menyebut kegagalannya. Kalimat
        // "prakiraan yang tampil di bawah" akan berbohong karena kartunya tidak menggambar
        // tabel sama sekali (Kondisi.tsx merender cuaca.message saja) -- ruling W3-6c:
        // diam di sini, pesan galat kartu cuaca jadi satu-satunya kabar (R-013).
        if (! $cuaca['available']) {
            return null;
        }

        $tanggalTrip = $trip->planned_date->toDateString();
        $cocok = array_values(array_filter(
            $cuaca['forecast'],
            fn (WeatherSnapshot $s) => $s->forecast_at->copy()->setTimezone($zona)->toDateString() === $tanggalTrip
        ));

        if ($cocok === []) {
            return [
                'status' => 'tidakTercakup',
                'pesan' => 'Prakiraan untuk '.$tanggal.' belum tersedia dari BMKG. Prakiraan yang tampil di bawah adalah yang terdekat.',
            ];
        }

        return [
            'status' => 'tersedia',
            'judul' => 'Prakiraan untuk '.$tanggal,
            'forecast' => array_map(fn (WeatherSnapshot $s) => $this->barisCuaca($s, $zona), $cocok),
        ];
    }

    /**
     * "Yang perlu disiapkan": izin bila jalurnya punya aturan tercatat, lalu butir templat
     * khusus jalur ini. Tanpa butir khusus, bagian ini menyebutnya dan daftar umum dipakai saat
     * trip direncanakan.
     *
     * @return array<int, array{judul: string, keterangan: string|null, wajib: bool}>
     */
    private function persiapan(Trail $trail, ?PermitRequirement $izin): array
    {
        $butir = $izin === null ? [] : [[
            'judul' => PreparationService::PERMIT_LABEL,
            'keterangan' => $izin->summary(),
            'wajib' => true,
        ]];

        foreach ($this->persiapan->khususJalur($trail) as $item) {
            $butir[] = ['judul' => $item->label, 'keterangan' => $item->description, 'wajib' => (bool) $item->is_critical];
        }

        return $butir;
    }

    /**
     * "Sumber di halaman ini" dalam urutan kepercayaan (R-009): setiap bagian menyebut jenis
     * dan nama sumbernya. Jenisnya dibaca dari data_sources, jadi "Resmi" hanya untuk catatan
     * bersumber resmi; label kecocokan selalu "Interpretasi sistem".
     *
     * @param  array<string, mixed>  $kondisi
     * @return array<int, array{bagian: string, jenis: string|null, isi: string, url: string|null}>
     */
    private function sumber(Trail $trail, array $kondisi, ?PermitRequirement $izin, bool $dinilai, int $jumlahLaporan, string $zona): array
    {
        $status = $kondisi['official_status'];
        $dikutip = $this->waktuDikutip($status);
        $baris = [
            [
                'bagian' => 'Status akses',
                'jenis' => $status['source_type'] ?? null,
                // Awalan dari kolom yang dipakai, sama dengan kartu status di atas (W3-2c).
                'isi' => implode(', ', array_filter([
                    $status['source'] ?? 'Belum tercatat',
                    $dikutip === null ? null : mb_strtolower($dikutip['awalan']).' '.Timezone::display(Carbon::instance($dikutip['waktu']), $zona),
                ])),
                'url' => null,
            ],
        ];

        if ($izin !== null) {
            $baris[] = [
                'bagian' => 'Izin',
                'jenis' => $izin->dataSource?->source_type?->value,
                'isi' => implode(', ', array_filter([$izin->source ?? $izin->authority, $izin->verified_at ? $this->waktu($izin->verified_at->toIso8601String(), $zona) : null])),
                'url' => $izin->booking_url,
            ];
        }

        $baris[] = ['bagian' => 'Cuaca', 'jenis' => null, 'isi' => (string) $kondisi['weather_context']['source'], 'url' => null];
        $baris[] = [
            'bagian' => 'Angka, pos, dan garis rute',
            'jenis' => $trail->dataSource?->source_type?->value,
            'isi' => $trail->dataSource?->source_name ?? 'Belum tercatat',
            'url' => $trail->dataSource?->source_url,
        ];

        if ($jumlahLaporan > 0) {
            $baris[] = ['bagian' => 'Kondisi lapangan', 'jenis' => SourceType::COMMUNITY->value, 'isi' => $jumlahLaporan.' laporan pendaki, termoderasi', 'url' => null];
        }

        if ($dinilai) {
            $baris[] = ['bagian' => 'Label kecocokan', 'jenis' => SourceType::AI_INTERPRETATION->value, 'isi' => 'aturan Route Fit '.str_replace('route-fit-', '', RouteFitService::ENGINE_VERSION), 'url' => null];
        }

        return $baris;
    }

    /**
     * Banding dari rincian: jalur ini bersama paling banyak dua simpanan terbaru lain yang
     * masih terbit (DK-05). Tanpa simpanan lain tidak ada yang dapat dibandingkan, jadi
     * tautannya tidak ada, bukan tombol mati (DK-40).
     *
     * @param  Collection<int, TrailConsideration>  $tersimpan
     */
    private function bandingUrl(Trail $trail, Collection $tersimpan): ?string
    {
        $lain = $tersimpan
            ->filter(fn (TrailConsideration $s) => $s->trail_id !== $trail->id && $s->trail?->is_published)
            ->take($this->consideration->batasBanding() - 1)
            ->pluck('trail_id');

        return $lain->isEmpty() ? null : route('trails.compare', ['trails' => $lain->prepend($trail->id)->join(',')]);
    }

    /**
     * @return array{label: string, nilai: string, ada: bool}
     */
    private function angka(string $label, string $nilai): array
    {
        return ['label' => $label, 'nilai' => $nilai, 'ada' => $nilai !== TrailFigures::BELUM];
    }

    /**
     * Penanda pos di peta rincian. Pos berkemah dan tujuan membawa aksen (DESIGN.md, satu
     * aksen), selalu bersama bentuk dan nomornya.
     *
     * @return array<int, array{lng: float, lat: float, label: string, urutan: int, aksen: string|null}>
     */
    private function penandaPos(Trail $trail): array
    {
        $pos = $trail->checkpoints->sortBy('sequence')->values();
        $terakhir = $pos->last();

        return $pos
            ->filter(fn ($p) => $p->latitude !== null && $p->longitude !== null)
            ->map(fn ($p) => [
                'lng' => (float) $p->longitude,
                'lat' => (float) $p->latitude,
                'label' => $p->sequence.'. '.$p->name,
                'urutan' => $p->sequence,
                'aksen' => CheckpointJourney::aksen($p, $terakhir),
            ])
            ->values()
            ->all();
    }

    /**
     * Status kosong bukan OPEN (§95): UNKNOWN dikirim tanpa warna status, dengan labelnya.
     *
     * Waktu dikirim bersama awalannya dari kolom yang benar-benar dipakai
     * (OfficialStatus::waktuDikutip(), W3-2c), dan kesegarannya dihitung dari stempel snapshot
     * saat halaman dirender (DataFreshnessService::keadaanDari(), I1 tinjauan akhir W3), jadi
     * catatan yang terbaca "Perlu ditinjau" di kartu Jelajah tidak tampak segar di sini.
     * Tanpa catatan (scope kosong) tidak ada kesegaran untuk disebut.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function statusResmi(array $snapshot, string $zona): array
    {
        $nilai = OfficialStatusValue::tryFrom((string) $snapshot['status']);
        $dikutip = $this->waktuDikutip($snapshot);
        $adaCatatan = ($snapshot['scope'] ?? null) !== null;

        return [
            'status' => $nilai !== null && $nilai !== OfficialStatusValue::UNKNOWN ? $nilai->value : null,
            'statusLabel' => $snapshot['status_label'],
            'scope' => StatusScope::tryFrom((string) $snapshot['scope'])?->label(),
            'source' => $snapshot['source'] ?? 'Belum tercatat',
            'jenisSumber' => $snapshot['source_type'] ?? null,
            'reason' => $snapshot['reason'],
            'waktu' => $dikutip === null ? null : Timezone::display(Carbon::instance($dikutip['waktu']), $zona),
            'awalanWaktu' => $dikutip['awalan'] ?? null,
            'kesegaran' => $adaCatatan
                ? $this->kesegaran->keadaanDari($this->stempel($snapshot['verified_at'] ?? null), $this->stempel($snapshot['expires_at'] ?? null))
                : null,
        ];
    }

    /**
     * Stempel waktu yang dikutip dari snapshot status resmi beserta awalannya.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array{waktu: CarbonInterface, awalan: string}|null
     */
    private function waktuDikutip(array $snapshot): ?array
    {
        return OfficialStatus::waktuDikutip(
            $this->stempel($snapshot['verified_at'] ?? null),
            $this->stempel($snapshot['published_at'] ?? null),
            $this->stempel($snapshot['effective_at'] ?? null),
        );
    }

    private function stempel(?string $iso): ?Carbon
    {
        return $iso === null ? null : Carbon::parse($iso);
    }

    /**
     * Sistem mencatat aturan pihak lain, tidak menggantikannya (PRD §36).
     *
     * @return array<string, mixed>|null
     */
    private function perizinan(?PermitRequirement $permit, string $zona): ?array
    {
        if ($permit === null) {
            return null;
        }

        return [
            'authority' => $permit->authority,
            'kuotaHarian' => $permit->daily_quota ? $permit->daily_quota.' pendaki' : null,
            'batasPemesanan' => $permit->booking_closes_days_before !== null ? 'H-'.$permit->booking_closes_days_before : null,
            'durasiMaksimum' => $permit->max_duration_days ? $permit->max_duration_days.' hari' : null,
            'pemandu' => $permit->guide_required ? 'Wajib pemandu terdaftar' : 'Tidak diwajibkan',
            'notes' => $permit->notes,
            'bookingUrl' => $permit->booking_url,
            'diverifikasi' => $this->waktu($permit->verified_at?->toIso8601String(), $zona),
            'source' => $permit->source ?? 'Belum dicatat',
            'jenisSumber' => $permit->dataSource?->source_type?->value,
        ];
    }

    /**
     * Kegagalan BMKG dilaporkan apa adanya, tidak pernah diganti cuaca karangan (R-013).
     *
     * @param  array<string, mixed>  $cuaca
     * @return array<string, mixed>
     */
    private function cuaca(array $cuaca): array
    {
        if (! $cuaca['available']) {
            return ['available' => false, 'message' => $cuaca['message'], 'source' => $cuaca['source']];
        }

        $zona = $cuaca['timezone'] ?? Timezone::DEFAULT;

        return [
            'available' => true,
            'referenceArea' => $cuaca['reference_area'] ?? 'Tidak dicatat',
            'message' => $cuaca['message'],
            'timezoneLabel' => $cuaca['timezone_label'] ?? 'WIB',
            'forecast' => array_map(fn (WeatherSnapshot $s) => $this->barisCuaca($s, $zona), array_slice($cuaca['forecast'], 0, 8)),
            'source' => $cuaca['source'],
            'freshnessLabel' => FreshnessState::tryFrom((string) $cuaca['freshness'])?->label(),
            'diperbarui' => $this->waktu($cuaca['fetched_at'], $zona),
        ];
    }

    /**
     * Satu baris prakiraan, dipakai delapan baris pertama kartu cuaca (cuaca()) dan baris
     * yang disaring menurut tanggal trip (untukTrip()), supaya format keduanya selalu sama.
     *
     * @return array{id: int, waktu: string, cuaca: string, suhu: string, kelembapan: string}
     */
    private function barisCuaca(WeatherSnapshot $s, string $zona): array
    {
        return [
            'id' => $s->id,
            'waktu' => $s->forecast_at->copy()->setTimezone($zona)->translatedFormat('d M H:i'),
            'cuaca' => $s->weather_description ?? '-',
            'suhu' => $s->temperature_c !== null ? number_format((float) $s->temperature_c, 1, ',', '.').' °C' : '-',
            'kelembapan' => $s->humidity_percent !== null ? $s->humidity_percent.'%' : '-',
        ];
    }

    /**
     * Pelapor disebut namanya: laporan bertanda tangan dinilai berbeda dari laporan
     * anonim. Status sudah-berterima-kasih dimuat dalam satu query untuk seluruh daftar,
     * bukan satu per laporan, supaya jumlah query tidak tumbuh bersama jumlah laporan.
     *
     * @param  array<string, mixed>  $komunitas
     * @param  array<int, TrailConditionReport>  $laporan
     * @return array<string, mixed>
     */
    private function komunitas(array $komunitas, array $laporan, User $pembaca): array
    {
        if (! $komunitas['available']) {
            return ['available' => false, 'message' => $komunitas['message'], 'reports' => []];
        }

        $sudah = $laporan === [] ? [] : ReportThank::query()
            ->where('user_id', $pembaca->id)
            ->whereIn('trail_condition_report_id', array_map(fn ($r) => $r->id, $laporan))
            ->pluck('trail_condition_report_id')
            ->all();

        return [
            'available' => true,
            'message' => null,
            'reports' => array_map(fn (TrailConditionReport $r) => [
                'id' => $r->id,
                'tags' => array_map(fn ($tag) => $tag->label(), $r->tags()),
                'note' => $r->note,
                'photoUrl' => $r->photo_path ? route('reports.photo', $r) : null,
                'photoAlt' => 'Foto kondisi jalur dari pendakian '.$r->hike_date->translatedFormat('d M Y'),
                // Tombol terima kasih tidak tampil pada laporan sendiri: angka yang dapat
                // dinaikkan sendirian tidak mengukur apa-apa.
                'milikSaya' => $r->user_id === $pembaca->id,
                'sudahTerimaKasih' => in_array($r->id, $sudah, true),
                'thanksCount' => (int) $r->thanks_count,
                'pelapor' => $r->user?->name ?? 'pendaki yang akunnya sudah dihapus',
                'dilaporkan' => $r->created_at->diffForHumans(),
                'tanggalPendakian' => $r->hike_date->translatedFormat('d M Y'),
                // Kesegaran dihitung dari tanggal pendakian, bukan tanggal kirim (§51),
                // supaya laporan yang mulai lama terbaca sebagai info lama.
                'kesegaran' => $this->conditions->reportFreshness($r)->label(),
                'segmen' => $r->segment?->name,
            ], $laporan),
        ];
    }

    private function waktu(?string $iso, string $zona): ?string
    {
        return $iso ? Timezone::display(Carbon::parse($iso), $zona) : null;
    }
}
