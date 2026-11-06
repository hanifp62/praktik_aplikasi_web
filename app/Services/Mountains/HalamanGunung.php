<?php

namespace App\Services\Mountains;

use App\Enums\RouteFitLabel;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\ConditionAggregatorService;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use App\Services\TrailNewsService;
use App\Services\Trails\SkalaProfil;
use App\Services\Trails\StatusJalurRingkas;
use App\Services\Trails\TrailFigures;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection;

/**
 * Props halaman gunung (spec v3 bagian 6.3, DK-44): hub jalur sebuah gunung, bukan pengganti
 * keputusan per jalur (R-001, R-002). Identitas gunung beserta ketinggian puncaknya disusun
 * MountainHeaderResource, di luar lapisan layanan (BR-02, LockedBusinessRulesTest).
 *
 * Yang ditampilkan berurutan dengan pertanyaan pendaki: gunung apa ini, bagaimana statusnya
 * di tingkat gunung, jalur mana yang dapat didaki dan seberapa cocok untuknya, bagaimana
 * mengurus izinnya, apa kata pendaki terakhir, dan dari mana semua keterangan itu. Hanya
 * jalur terbit yang tampil (R-022); jalur yang datanya belum lengkap hanya dihitung.
 *
 * Kueri tidak tumbuh bersama jumlah jalur: kecocokan, status, dan laporan dimuat batch.
 */
final class HalamanGunung
{
    /** Laporan komunitas terbaru yang ditampilkan, dari jalur mana pun di gunung ini. */
    public const MAKS_LAPORAN = 3;

    public function __construct(
        private readonly TrailFitService $fit,
        private readonly StatusJalurRingkas $statusJalur,
        private readonly OfficialStatusService $statusResmi,
        private readonly TrailNewsService $kabar,
        private readonly ConditionAggregatorService $kondisi,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(Mountain $gunung, User $pembaca): array
    {
        $zona = Timezone::isSupported($gunung->timezone) ? $gunung->timezone : Timezone::DEFAULT;
        $gunung->loadMissing('dataSource:id,source_name,source_type,verified_at');

        /** @var Collection<int, Trail> $jalur */
        $jalur = $gunung->trails()->published()->with('mountain')->orderBy('name')->get();
        $fit = $pembaca->hasCompletedProfile() ? $this->fit->forTrails($pembaca, $jalur) : [];
        $status = $this->statusJalur->untuk($jalur);

        return [
            'mengikuti' => $this->kabar->follows($pembaca, $gunung),
            // MountainFollowController hanya menerima gunung yang masih punya jalur beredar;
            // tombolnya tidak ditawarkan bila aksinya akan ditolak (DK-40).
            'bolehDiikuti' => $gunung->trails()->whereNull('archived_at')->exists(),
            'status' => $this->statusGunung($gunung, $zona),
            'jalur' => $jalur->map(fn (Trail $t) => [
                'id' => $t->id,
                'nama' => $t->name,
                'url' => route('trails.show', $t),
                'fit' => isset($fit[$t->id]) ? [
                    'label' => $fit[$t->id]->label?->value,
                    'labelText' => RouteFitLabel::textFor($fit[$t->id]->label),
                    'alasan' => $fit[$t->id]->alasan,
                    'denganRencana' => $fit[$t->id]->denganRencana,
                ] : null,
                'angka' => TrailFigures::ringkas($t),
                'medan' => $t->terrainTypes() === [] ? null : TrailFigures::medan($t),
                'status' => $status[$t->id],
            ])->values()->all(),
            'jumlahMenunggu' => $gunung->trails()->active()->where('is_published', false)->count(),
            'profil' => $this->profil($jalur),
            'izin' => $this->izin($gunung, $zona),
            'kondisi' => $this->laporan($jalur),
            // Jendela laporan dalam kalimat kosongnya, dari config yang sama dengan kuerinya (T9-M5).
            'hariLaporan' => (int) config('hiking.conditions.community_recent_days', 30),
            'provenans' => [
                'sumber' => $gunung->dataSource === null ? null : [
                    'jenis' => $gunung->dataSource->source_type?->value,
                    'label' => $gunung->dataSource->source_type?->label(),
                    'nama' => $gunung->dataSource->source_name,
                ],
                'diverifikasi' => $gunung->dataSource?->verified_at?->copy()->setTimezone($zona)->translatedFormat('j M Y'),
            ],
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
        ];
    }

    /**
     * Status akses tingkat gunung. Buka di tingkat gunung tidak berarti setiap jalurnya buka
     * (§42, R-012); halaman menulis kalimat itu di samping status ini.
     *
     * @return array<string, mixed>
     */
    private function statusGunung(Mountain $gunung, string $zona): array
    {
        $catatan = $this->statusResmi->currentFor($gunung);

        if ($catatan instanceof OfficialStatus) {
            $catatan->loadMissing('dataSource:id,source_type');
        }

        return $this->statusJalur->bentuk($catatan, $zona);
    }

    /**
     * Visual data pengganti foto (spec v3 bagian 8.7, DK-37): profil setiap jalur terbit pada
     * skala mutlak yang sama. Tanpa satu pun profil tidak ada visual; kalimatnya tetap ada.
     *
     * @param  Collection<int, Trail>  $jalur
     * @return array<string, mixed>|null
     */
    private function profil(Collection $jalur): ?array
    {
        $skala = SkalaProfil::mutlakBersama($jalur->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all());

        if ($skala['garis'] === []) {
            return null;
        }

        $nama = $jalur->pluck('name', 'id');
        // P-9 (perbaikan 1): jalur terbit tanpa profil tidak digambar (R-007, tidak ada
        // garis karangan), tetapi keberadaannya tidak boleh diam saja -- pembaca yang
        // membandingkan jumlah jalur di "Pilih jalur" dengan jumlah garis di sini berhak
        // tahu kenapa keduanya berbeda.
        $tanpaProfil = $jalur->count() - count($skala['garis']);

        return [
            'garis' => collect($skala['garis'])->map(fn (array $titik, int $id) => ['id' => $id, 'nama' => $nama[$id], 'titik' => $titik])->values()->all(),
            'keterangan' => 'Profil elevasi '.count($skala['garis']).' jalur pada skala yang sama, ketinggian '
                .number_format($skala['mMin'], 0, ',', '.').' sampai '.number_format($skala['mMaks'], 0, ',', '.')
                .' m dan jarak sampai '.number_format($skala['kmMaks'], 1, ',', '.').' km: '
                .$jalur->filter(fn (Trail $t) => isset($skala['garis'][$t->id]))
                    ->map(fn (Trail $t) => $t->name.' naik '.number_format((int) $t->elevation_gain_m, 0, ',', '.').' m dalam '.number_format((float) $t->distance_km, 1, ',', '.').' km')
                    ->join('; ').'.'
                .($tanpaProfil > 0 ? ' '.$tanpaProfil.' jalur terbit lain belum punya profil elevasi.' : ''),
        ];
    }

    /**
     * Aturan izin tingkat gunung (PRD §153): sistem menautkan sistem resmi dan tidak pernah
     * menjual atau memesan izin. Kuota yang tidak bersumber tidak ditebak.
     *
     * @return array<string, mixed>|null
     */
    private function izin(Mountain $gunung, string $zona): ?array
    {
        $aturan = PermitRequirement::query()
            ->where('mountain_id', $gunung->id)
            ->whereNull('trail_id')
            ->with('dataSource:id,source_type')
            ->terverifikasiDulu()
            ->first();

        if ($aturan === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;

        return [
            'pengelola' => $aturan->authority,
            'jendela' => match (true) {
                $buka !== null && $tutup !== null => "Dibuka H-{$buka}, ditutup H-{$tutup} dari tanggal naik",
                $tutup !== null => "Ditutup H-{$tutup} dari tanggal naik",
                $buka !== null => "Dibuka H-{$buka} dari tanggal naik",
                default => null,
            },
            'kuota' => $aturan->daily_quota ? number_format($aturan->daily_quota, 0, ',', '.').' pendaki per hari' : null,
            'pemandu' => $aturan->guide_required ? 'Wajib pemandu terdaftar' : null,
            'catatan' => $aturan->notes,
            'bookingUrl' => $aturan->booking_url,
            'sumber' => [
                'jenis' => $aturan->dataSource?->source_type?->value,
                'label' => $aturan->dataSource?->source_type?->label(),
                'nama' => $aturan->source,
            ],
            'diverifikasi' => $aturan->verified_at?->copy()->setTimezone($zona)->translatedFormat('j M Y'),
        ];
    }

    /**
     * Laporan komunitas terbaru dari jalur terbit gunung ini, termoderasi (R-010): pelengkap
     * status resmi, bukan penggantinya. Kesegarannya dihitung dari tanggal pendakian (§51).
     *
     * @param  Collection<int, Trail>  $jalur
     * @return array<int, array<string, mixed>>
     */
    private function laporan(Collection $jalur): array
    {
        if ($jalur->isEmpty()) {
            return [];
        }

        return TrailConditionReport::query()
            ->visibleToPublic()
            ->whereIn('trail_id', $jalur->pluck('id'))
            // Jendela dari config dan tanggal Indonesia, sama dengan Banding (T9-M5).
            ->dalamJendelaTerbaru()
            ->with('trail:id,name')
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->limit(self::MAKS_LAPORAN)
            ->get()
            ->map(fn (TrailConditionReport $r) => [
                'id' => $r->id,
                'jalur' => $r->trail->name,
                'tags' => array_map(fn ($tag) => $tag->label(), $r->tags()),
                'catatan' => $r->note,
                'dilaporkan' => $r->created_at->diffForHumans(),
                'tanggalPendakian' => $r->hike_date->translatedFormat('j M'),
                'kesegaran' => $this->kondisi->reportFreshness($r)->label(),
            ])
            ->all();
    }
}
