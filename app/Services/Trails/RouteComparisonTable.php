<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Models\Trail;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use Illuminate\Support\Collection;

/**
 * Tabel banding (FR-06). Sistem tidak pernah menyatakan pemenang (PRD §33, R-016): yang
 * ditandai hanya apakah suatu aspek berbeda antarjalur, tidak pernah mana yang lebih
 * baik. Tidak ada urutan menurut nilai dan tidak ada sorotan nilai terkecil/terbesar.
 *
 * Barisnya dimensi ongkos, bukan daftar kolom basis data: tujuan pengguna yang
 * sebenarnya, menurut riset AllTrails, adalah memperkirakan waktu dan tenaga yang harus
 * ia keluarkan. Urutannya mengikuti itu, yang paling menentukan keputusan lebih dulu.
 */
class RouteComparisonTable
{
    public function __construct(
        private readonly OfficialStatusService $officialStatus,
        private readonly TrailFitService $fit,
    ) {}

    /**
     * @param  Collection<int, Trail>  $trails
     * @return array{jalur: array<int, array<string, mixed>>, baris: array<int, array{label: string, nilai: array<int, string>, berbeda: bool|null}>, beda: array{fit: bool|null, status: bool|null}}
     */
    public function susun(Collection $trails, User $user): array
    {
        // Kecocokan dinilai hanya untuk profil yang cukup: menilai tanpa profil
        // menghasilkan label yang terlihat pasti dan berdasar ketiadaan (§91).
        $ringkasanFit = $user->hasCompletedProfile() ? $this->fit->forTrails($user, $trails) : [];

        // Satu panggilan batch, bukan satu per jalur: effectiveStatusForTrail() di dalam
        // loop menambah dua query tak ter-cache per kolom (§96).
        $statuses = $this->officialStatus->effectiveStatusesForTrails($trails);

        $jalur = $trails->values()->map(function (Trail $t) use ($ringkasanFit, $statuses) {
            $fit = $ringkasanFit[$t->id] ?? null;
            $status = $statuses[$t->id] ?? null;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'mountain' => $t->mountain?->name,
                'url' => route('trails.show', $t),
                'fit' => $fit === null ? null : [
                    'label' => $fit->label?->value,
                    'labelText' => $fit->label?->label() ?? 'Tidak dinilai',
                    'alasan' => $fit->alasan,
                    'denganRencana' => $fit->denganRencana,
                ],
                'status' => [
                    'status' => $status !== null && $status !== OfficialStatusValue::UNKNOWN ? $status->value : null,
                    'label' => $status?->label(),
                ],
            ];
        })->all();

        return [
            'jalur' => $jalur,
            'baris' => $this->baris($trails),
            'beda' => [
                'fit' => self::berbeda(array_map(fn ($j) => $j['fit']['labelText'] ?? 'Belum dinilai', $jalur)),
                'status' => self::berbeda(array_map(fn ($j) => (string) $j['status']['label'], $jalur)),
            ],
        ];
    }

    /**
     * Baris ongkos: karakteristik jalur yang sama bagi setiap pembaca, tanpa kecocokan.
     * Dipakai juga halaman tautan bagikan, yang pembacanya tidak punya profil.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array{label: string, nilai: array<int, string>, berbeda: bool|null}>
     */
    public function baris(Collection $trails): array
    {
        $baris = [
            'Waktu' => fn (Trail $t) => TrailFigures::durasi($t->estimated_duration_minutes),
            'Jarak' => fn (Trail $t) => TrailFigures::jarak($t->distance_km),
            'Tanjakan' => fn (Trail $t) => TrailFigures::meter($t->elevation_gain_m),
            'Kecuraman' => fn (Trail $t) => TrailFigures::kecuraman($t),
            'Tuntutan teknis' => fn (Trail $t) => $t->technical_demand?->label() ?? TrailFigures::BELUM,
            'Kerumitan navigasi' => fn (Trail $t) => $t->navigation_complexity?->label() ?? TrailFigures::BELUM,
            'Air' => fn (Trail $t) => $t->water_availability?->label() ?? TrailFigures::BELUM,
            'Berkemah' => fn (Trail $t) => $t->camping_available ? 'Bisa' : 'Tidak',
            'Medan' => fn (Trail $t) => TrailFigures::medan($t),
        ];

        return collect($baris)->map(function (callable $nilai, string $label) use ($trails) {
            $isi = $trails->values()->map($nilai)->all();

            return ['label' => $label, 'nilai' => $isi, 'berbeda' => self::berbeda($isi)];
        })->values()->all();
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
}
