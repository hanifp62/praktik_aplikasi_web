<?php

namespace App\Services\Trails;

use App\Enums\RouteFitLabel;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\PermitService;
use App\Services\TrailFitService;
use Illuminate\Support\Collection;

/**
 * Tabel banding (FR-06, spec v3 bagian 6.7, prototype 07). Sistem tidak pernah menyatakan
 * pemenang (PRD §33, R-016): yang ditandai hanya apakah suatu aspek berbeda antarjalur,
 * tidak pernah mana yang lebih baik. Tidak ada urutan menurut nilai dan tidak ada sorotan
 * nilai terkecil/terbesar.
 *
 * Barisnya dimensi ongkos, bukan daftar kolom basis data: tujuan pengguna yang sebenarnya,
 * menurut riset AllTrails, adalah memperkirakan waktu dan tenaga yang harus ia keluarkan.
 * Urutannya mengikuti itu, yang paling menentukan keputusan lebih dulu. Sejak W3 kolomnya
 * juga membawa status resmi beserta sumber dan waktunya, laporan pendaki terbaru (lapisan
 * komunitas, jendela hiking.conditions.community_recent_days), izin, dan garis profil pada
 * skala tinggi yang sama; semuanya batch, jadi kueri tidak tumbuh bersama jumlah kolom.
 */
class RouteComparisonTable
{
    /** Penanda kolom tanpa laporan untuk perbandingan "berbeda"; bukan teks yang ditampilkan. */
    private const TANPA_LAPORAN = "\0tanpa-laporan";

    public function __construct(
        private readonly TrailFitService $fit,
        private readonly StatusJalurRingkas $status,
        private readonly PermitService $permits,
    ) {}

    /**
     * @param  Collection<int, Trail>  $trails
     * @return array{jalur: array<int, array<string, mixed>>, baris: array<int, array{kunci: string, label: string, nilai: array<int, string>, berbeda: bool|null}>, beda: array<string, bool|null>, profil: string|null, hariLaporan: int}
     */
    public function susun(Collection $trails, User $user): array
    {
        // Kecocokan dinilai hanya untuk profil yang cukup: menilai tanpa profil
        // menghasilkan label yang terlihat pasti dan berdasar ketiadaan (§91).
        $ringkasanFit = $user->hasCompletedProfile() ? $this->fit->forTrails($user, $trails) : [];
        $status = $this->status->untuk($trails);
        $izin = $this->permits->requirementsForTrails($trails);
        $laporan = $this->laporanTerbaru($trails);
        $garis = SkalaProfil::naikBersama($trails->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all())['garis'];
        $tanjakanTerbesar = max(1, (int) $trails->max('elevation_gain_m'));

        $jalur = $trails->values()->map(function (Trail $t) use ($ringkasanFit, $status, $izin, $laporan, $garis, $tanjakanTerbesar) {
            $fit = $ringkasanFit[$t->id] ?? null;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'mountain' => $t->mountain?->name,
                'url' => route('trails.show', $t),
                'fit' => $fit === null ? null : [
                    'label' => $fit->label?->value,
                    'labelText' => RouteFitLabel::textFor($fit->label),
                    'alasan' => $fit->alasan,
                    'denganRencana' => $fit->denganRencana,
                ],
                'status' => $status[$t->id],
                'laporan' => $laporan[$t->id] ?? null,
                'izin' => $this->kataIzin($izin[$t->id] ?? null),
                // Bilah tanjakan pada skala yang sama: pecahan dari tanjakan terbesar di tabel
                // ini, bukan peringkat; tanpa tanjakan tidak ada bilah (R-007).
                'rasioTanjakan' => $t->elevation_gain_m === null ? null : round($t->elevation_gain_m / $tanjakanTerbesar, 3),
                'garis' => $garis[$t->id] ?? null,
                'rencanaUrl' => route('trips.create', ['trail' => $t->id]),
            ];
        })->all();

        return [
            'jalur' => $jalur,
            'baris' => $this->baris($trails),
            'beda' => [
                'fit' => self::berbeda(array_map(fn ($j) => $j['fit']['labelText'] ?? RouteFitLabel::UNRATED_TEXT, $jalur)),
                'status' => self::berbeda(array_map(fn ($j) => (string) $j['status']['label'], $jalur)),
                // Kolom tanpa laporan berbeda dari kolom yang laporannya tanpa tanda (T9-M4): penanda
                // ketiadaan tidak pernah sama dengan daftar tanda kosong.
                'laporan' => self::berbeda(array_map(fn ($j) => $j['laporan'] === null ? self::TANPA_LAPORAN : implode(', ', $j['laporan']['tags']), $jalur)),
                'izin' => self::berbeda(array_column($jalur, 'izin')),
            ],
            'profil' => $this->keteranganProfil($trails, $garis),
            'hariLaporan' => (int) config('hiking.conditions.community_recent_days', 30),
        ];
    }

    /**
     * Baris ongkos: karakteristik jalur yang sama bagi setiap pembaca, tanpa kecocokan.
     * Dipakai juga halaman tautan bagikan, yang pembacanya tidak punya profil.
     *
     * Setiap baris membawa `kunci` yang tetap, supaya komponen mencocokkan baris (misalnya bilah
     * tanjakan) lewat kunci, bukan lewat string label yang dapat berubah (T9-M7 tinjauan akhir W3).
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array{kunci: string, label: string, nilai: array<int, string>, berbeda: bool|null}>
     */
    public function baris(Collection $trails): array
    {
        $baris = [
            ['waktu', 'Waktu', fn (Trail $t) => TrailFigures::durasi($t->estimated_duration_minutes)],
            ['jarak', 'Jarak', fn (Trail $t) => TrailFigures::jarak($t->distance_km)],
            ['tanjakan', 'Tanjakan', fn (Trail $t) => TrailFigures::meter($t->elevation_gain_m)],
            ['kecuraman', 'Kecuraman', fn (Trail $t) => TrailFigures::kecuraman($t)],
            ['teknis', 'Tuntutan teknis', fn (Trail $t) => $t->technical_demand?->label() ?? TrailFigures::BELUM],
            ['navigasi', 'Kerumitan navigasi', fn (Trail $t) => $t->navigation_complexity?->label() ?? TrailFigures::BELUM],
            ['air', 'Air', fn (Trail $t) => $t->water_availability?->label() ?? TrailFigures::BELUM],
            ['berkemah', 'Berkemah', fn (Trail $t) => $t->camping_available ? 'Bisa' : 'Tidak'],
            ['medan', 'Medan', fn (Trail $t) => TrailFigures::medan($t)],
        ];

        return array_map(function (array $satu) use ($trails) {
            [$kunci, $label, $nilai] = $satu;
            $isi = $trails->values()->map($nilai)->all();

            return ['kunci' => $kunci, 'label' => $label, 'nilai' => $isi, 'berbeda' => self::berbeda($isi)];
        }, $baris);
    }

    /**
     * Null ketika belum ada yang dapat dibandingkan: satu kolom tidak berbeda dari apa pun.
     *
     * @param  array<int, string>  $nilai
     */
    public static function berbeda(array $nilai): ?bool
    {
        return count($nilai) < 2 ? null : count(array_unique($nilai)) > 1;
    }

    /**
     * Laporan termoderasi terbaru per jalur dalam jendela terbaru komunitas (R-010: pelengkap
     * status resmi, bukan penggantinya), satu kueri untuk semua kolom. Jendelanya dari config
     * dan tanggal Indonesia (TrailConditionReport::scopeDalamJendelaTerbaru(), T9-M5).
     * Kesegarannya dihitung dari tanggal pendakian (§51).
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array{tags: array<int, string>, kapan: string}>
     */
    private function laporanTerbaru(Collection $trails): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        return TrailConditionReport::query()
            ->visibleToPublic()
            ->whereIn('trail_id', $trails->pluck('id'))
            ->dalamJendelaTerbaru()
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->get()
            ->unique('trail_id')
            ->mapWithKeys(fn (TrailConditionReport $r) => [$r->trail_id => [
                'tags' => array_map(fn ($tag) => $tag->label(), $r->tags()),
                'kapan' => 'pendakian '.$r->hike_date->translatedFormat('j M'),
            ]])
            ->all();
    }

    /**
     * Izin dalam kata: aturan yang tercatat, atau pengakuan bahwa belum ada sumbernya.
     * Aturan pihak lain dicatat, tidak digantikan (PRD §36). Aturan yang belum diverifikasi
     * menyebutnya (T9-M6 tinjauan akhir W3), supaya tidak terbaca setara dengan yang sudah.
     */
    private function kataIzin(?PermitRequirement $izin): string
    {
        if ($izin === null) {
            return 'Belum ada aturan tercatat';
        }

        return 'Pesan lewat '.$izin->authority
            .($izin->booking_closes_days_before !== null ? ', tutup H-'.$izin->booking_closes_days_before : '')
            .($izin->verified_at === null ? ' (belum diverifikasi)' : '');
    }

    /**
     * Keterangan profil pada skala tinggi yang sama, dalam kalimat, tanpa menyebut mana yang
     * lebih baik (R-016). Hanya bila paling sedikit dua garis profil benar-benar digambar
     * (T9-M3 tinjauan akhir W3): tanpa itu tidak ada "skala sama" yang dapat dibaca.
     *
     * @param  Collection<int, Trail>  $trails
     * @param  array<int, array<int, array{0: float, 1: float}>|null>  $garis  garis per id jalur (SkalaProfil::naikBersama)
     */
    private function keteranganProfil(Collection $trails, array $garis): ?string
    {
        if (count(array_filter($garis, fn ($satu) => $satu !== null)) < 2) {
            return null;
        }

        $bagian = $trails
            ->filter(fn (Trail $t) => $t->elevation_gain_m !== null && $t->distance_km !== null)
            ->map(fn (Trail $t) => $t->name.' naik ↑'.number_format($t->elevation_gain_m, 0, ',', '.').' m dalam '.number_format((float) $t->distance_km, 1, ',', '.').' km')
            ->values();

        return $bagian->count() < 2 ? null : 'Skala tinggi sama untuk semua jalur: '.$bagian->join('; ').'.';
    }
}
