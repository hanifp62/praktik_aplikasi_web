<?php

namespace App\Services\Trails;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\StatusScope;
use App\Models\PermitRequirement;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\CheckpointPaceService;
use App\Services\ConditionAggregatorService;
use App\Services\ConsiderationService;
use App\Services\PermitService;
use App\Services\ProgressLadderService;
use App\Services\RouteFitService;
use App\Services\TrailNewsService;
use App\Support\FaktorKategori;
use App\Support\Timezone;
use Illuminate\Support\Carbon;

/**
 * Props halaman rinci jalur (FR-07), sudah diformat di server: overview, rute, pos,
 * konteks persiapan, status resmi, cuaca, dan laporan komunitas, masing-masing dengan
 * sumbernya sendiri (PRD §34, §60, §92).
 *
 * Waktu ditampilkan dalam zona gunungnya lengkap dengan penanda (§93). Skor internal,
 * bobot, dan probabilitas tidak pernah ikut (BR-09): faktor kecocokan sampai ke klien
 * hanya sebagai kategori lewat FaktorKategori.
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
     * @return array<string, mixed>
     */
    public function props(Trail $trail, User $user): array
    {
        $profilLengkap = $user->hasCompletedProfile();

        // Halaman ini menilai dengan rencana terbaru pengguna kalau ada, berbeda dari baris
        // daftar yang menilai tanpa goal. 'denganRencana' memberi tahu pembaca jenis
        // penilaian mana yang sedang ia lihat, dengan kalimat yang sama seperti FitLine.
        $goal = $profilLengkap ? $user->hikingGoals()->latest()->first() : null;
        // Izin diambil sekali dan dioper ke mesin; tanpa itu evaluate() mencarinya lagi.
        $izin = $this->permits->requirementFor($trail);
        $fit = $profilLengkap ? $this->routeFit->evaluate($user, $goal, $trail, permit: $izin) : null;

        $kondisi = $this->conditions->forTrail($trail);
        $zona = Timezone::forTrail($trail);
        $laporan = $kondisi['community_context']['reports'] ?? [];
        $tersimpan = $this->consideration->forUser($user);

        return [
            'fit' => $fit === null ? null : [
                'label' => $fit->label?->value,
                'labelText' => RouteFitLabel::textFor($fit->label),
                'deskripsi' => $fit->label?->description(),
                'denganRencana' => $goal !== null,
                'faktor' => FaktorKategori::dari($fit->factorsToArray()),
                // Acuannya diri sendiri, bukan pendaki lain. Dihitung hanya ketika blok
                // "Mengapa demikian" memang tampil, jadi query-nya tidak dibayar untuk
                // pendaki yang profilnya belum lengkap.
                'tangga' => $this->progressLadder->bandingkanDenganRiwayat($user, $trail),
            ],
            'angkaKunci' => [
                $this->angka('Jarak', TrailFigures::jarak($trail->distance_km)),
                $this->angka('Estimasi durasi', TrailFigures::durasi($trail->estimated_duration_minutes)),
                $this->angka('Elevation gain', TrailFigures::meter($trail->elevation_gain_m)),
                $this->angka('Tingkat teknis', $trail->technical_demand?->label() ?? TrailFigures::BELUM),
            ],
            'rincian' => [
                $this->angka('Elevation loss', TrailFigures::meter($trail->elevation_loss_m)),
                $this->angka('Navigasi', $trail->navigation_complexity?->label() ?? TrailFigures::BELUM),
                $this->angka('Sumber air', $trail->water_availability?->label() ?? TrailFigures::BELUM),
                $this->angka('Camping', $trail->camping_available ? 'Tersedia' : 'Tidak tersedia'),
                $this->angka('Titik awal', $trail->starting_point ?? TrailFigures::BELUM),
            ],
            'medan' => array_map(fn ($t) => $t->label(), $trail->terrainTypes()),
            'profilElevasi' => $this->profilElevasi($trail->elevation_profile),
            // Bukan 'peta': nama itu milik props bersama (konfigurasi basemap).
            'petaJalur' => [
                'geometry' => $trail->readGeoJson('geometry'),
                'penanda' => $this->penandaPos($trail),
            ],
            // Waktu tempuh antarpos dari rekaman pendaki; di-cache mengikuti TTL publik.
            'pos' => CheckpointJourney::susun($trail->checkpoints, $this->paces->forTrail($trail)),
            'segmen' => $trail->segments->map(fn ($s) => [
                'id' => $s->id,
                'judul' => $s->sequence.'. '.$s->name,
                'ringkas' => implode(' · ', [
                    $s->distance_km !== null ? number_format((float) $s->distance_km, 1, ',', '.').' km' : 'jarak belum tersedia',
                    $s->elevation_gain_m !== null ? 'gain '.number_format($s->elevation_gain_m, 0, ',', '.').' m' : 'gain belum tersedia',
                    'teknis '.($s->technical_demand?->label() ?? 'belum tersedia'),
                ]),
                'description' => $s->description,
            ])->values()->all(),
            'statusResmi' => $this->statusResmi($kondisi['official_status'], $zona),
            'perizinan' => $this->perizinan($izin, $zona),
            'cuaca' => $this->cuaca($kondisi['weather_context']),
            'komunitas' => $this->komunitas($kondisi['community_context'], $laporan, $user),
            'sumberData' => [
                'jalur' => $trail->dataSource?->source_name ?? 'Belum tercatat',
                'jalurUrl' => $trail->dataSource?->source_url,
                'statusResmi' => $kondisi['official_status']['source'] ?? 'Belum tercatat',
                'cuaca' => $kondisi['weather_context']['source'],
            ],
            'mengikutiGunung' => $this->news->follows($user, $trail->mountain),
            'ditimbang' => $tersimpan->contains('id', $trail->id),
            'jumlahTersimpan' => $tersimpan->count(),
        ];
    }

    /**
     * @return array{label: string, nilai: string, ada: bool}
     */
    private function angka(string $label, string $nilai): array
    {
        return ['label' => $label, 'nilai' => $nilai, 'ada' => $nilai !== TrailFigures::BELUM];
    }

    /**
     * Satu titik bukan profil: menggambarnya menghasilkan garis tanpa arti yang terbaca
     * seperti jalur datar. Kurang dari dua titik sah dikirim sebagai tidak ada, supaya
     * halaman menyebutnya belum tersedia alih-alih menggambar grafik kosong.
     *
     * @param  array<int, mixed>|null  $profil
     * @return array<int, array{km: float, m: float}>|null
     */
    private function profilElevasi(?array $profil): ?array
    {
        $titik = array_values(array_filter(
            (array) $profil,
            fn ($t) => is_array($t) && is_numeric($t['km'] ?? null) && is_numeric($t['m'] ?? null)
        ));

        if (count($titik) < 2) {
            return null;
        }

        return array_map(fn (array $t) => ['km' => (float) $t['km'], 'm' => (float) $t['m']], $titik);
    }

    /**
     * @return array<int, array{lng: float, lat: float, label: string}>
     */
    private function penandaPos(Trail $trail): array
    {
        return $trail->checkpoints
            ->filter(fn ($pos) => $pos->latitude !== null && $pos->longitude !== null)
            ->sortBy('sequence')
            ->map(fn ($pos) => ['lng' => (float) $pos->longitude, 'lat' => (float) $pos->latitude, 'label' => $pos->sequence.'. '.$pos->name])
            ->values()
            ->all();
    }

    /**
     * Status kosong bukan OPEN (§95): UNKNOWN dikirim tanpa warna status, dengan labelnya.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function statusResmi(array $snapshot, string $zona): array
    {
        $nilai = OfficialStatusValue::tryFrom((string) $snapshot['status']);

        return [
            'status' => $nilai !== null && $nilai !== OfficialStatusValue::UNKNOWN ? $nilai->value : null,
            'statusLabel' => $snapshot['status_label'],
            'scope' => StatusScope::tryFrom((string) $snapshot['scope'])?->label(),
            'source' => $snapshot['source'] ?? 'Belum tercatat',
            'reason' => $snapshot['reason'],
            'diverifikasi' => $this->waktu($snapshot['verified_at'] ?? $snapshot['published_at'], $zona),
        ];
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
            'forecast' => array_map(fn (WeatherSnapshot $s) => [
                'id' => $s->id,
                'waktu' => $s->forecast_at->copy()->setTimezone($zona)->translatedFormat('d M H:i'),
                'cuaca' => $s->weather_description ?? '-',
                'suhu' => $s->temperature_c !== null ? number_format((float) $s->temperature_c, 1, ',', '.').' °C' : '-',
                'kelembapan' => $s->humidity_percent !== null ? $s->humidity_percent.'%' : '-',
            ], array_slice($cuaca['forecast'], 0, 8)),
            'source' => $cuaca['source'],
            'freshnessLabel' => FreshnessState::tryFrom((string) $cuaca['freshness'])?->label(),
            'diperbarui' => $this->waktu($cuaca['fetched_at'], $zona),
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
