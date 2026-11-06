<?php

namespace App\Models;

use App\Enums\RouteFitLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recommendation_run_id', 'trail_id', 'trail_snapshot', 'eligible', 'label', 'internal_score',
    'matched_factors', 'failed_rules', 'warnings', 'explanation', 'rank',
])]
// BR-09: internal_score is for ranking and audit only and must never reach the user as a score.
#[Hidden(['internal_score'])]
class RecommendationResult extends Model
{
    use HasFactory;

    /**
     * Karakteristik jalur yang ikut menentukan label, sebagai satu bentuk baku.
     *
     * Daftarnya hanya ada di sini supaya sisi tulis dan sisi baca tidak pernah bergeser
     * sendiri-sendiri. Isinya persis medan yang dipakai CompatibilityScorer, bukan hanya
     * yang ditampilkan kartu: medan yang ikut dinilai tetapi tidak terlihat juga
     * membatalkan labelnya ketika berubah.
     *
     * @return array<string, mixed>
     */
    public static function snapshotOf(Trail $trail): array
    {
        return [
            'distance_km' => $trail->distance_km,
            'elevation_gain_m' => $trail->elevation_gain_m,
            'elevation_loss_m' => $trail->elevation_loss_m,
            'estimated_duration_minutes' => $trail->estimated_duration_minutes,
            'technical_demand' => $trail->technical_demand?->value,
            'navigation_complexity' => $trail->navigation_complexity?->value,
            'camping_available' => $trail->camping_available,
            'terrain_character' => $trail->terrain_character,
        ];
    }

    /**
     * Angka jalur sebagaimana dipakai menilai.
     *
     * Baris yang ditulis sebelum kolomnya ada jatuh kembali ke nilai sekarang, persis
     * seperti perilaku sebelumnya. Ia tidak dapat direkayasa ulang secara jujur, jadi ia
     * tidak mengaku tahu yang tidak diketahuinya.
     *
     * @return array<string, mixed>
     */
    public function trailFigures(): array
    {
        return $this->trail_snapshot ?? self::snapshotOf($this->trail);
    }

    /**
     * Apakah jalurnya sudah berbeda dari yang dipakai menilai.
     *
     * Baris tanpa snapshot menjawab tidak, bukan karena jalurnya pasti tidak berubah,
     * melainkan karena tidak ada pembanding. Menjawab ya di situ berarti menuduh
     * berdasarkan ketiadaan bukti.
     */
    public function trailHasChangedSinceRun(): bool
    {
        if ($this->trail_snapshot === null) {
            return false;
        }

        return $this->bentukBaku($this->trail_snapshot) !== $this->bentukBaku(self::snapshotOf($this->trail));
    }

    /**
     * Angka dibulatkan dan medan diurutkan sebelum dibandingkan.
     *
     * Jarak melewati kolom decimal dan json, dan keduanya dapat mengembalikannya sebagai
     * string dengan jumlah angka di belakang koma yang berbeda. Tanpa pembakuan ini,
     * 8.5 dan "8.50" terbaca sebagai perubahan dan penandanya menyala tanpa ada yang
     * berubah.
     *
     * @param  array<string, mixed>  $angka
     * @return array<string, mixed>
     */
    private function bentukBaku(array $angka): array
    {
        $medan = array_values(array_filter((array) ($angka['terrain_character'] ?? [])));
        sort($medan);

        return [
            'distance_km' => isset($angka['distance_km']) ? round((float) $angka['distance_km'], 2) : null,
            'elevation_gain_m' => isset($angka['elevation_gain_m']) ? (int) $angka['elevation_gain_m'] : null,
            'elevation_loss_m' => isset($angka['elevation_loss_m']) ? (int) $angka['elevation_loss_m'] : null,
            'estimated_duration_minutes' => isset($angka['estimated_duration_minutes'])
                ? (int) $angka['estimated_duration_minutes']
                : null,
            'technical_demand' => $angka['technical_demand'] ?? null,
            'navigation_complexity' => $angka['navigation_complexity'] ?? null,
            'camping_available' => (bool) ($angka['camping_available'] ?? false),
            'terrain_character' => $medan,
        ];
    }

    protected function casts(): array
    {
        return [
            'eligible' => 'boolean',
            'label' => RouteFitLabel::class,
            'internal_score' => 'float',
            'trail_snapshot' => 'array',
            'matched_factors' => 'array',
            'failed_rules' => 'array',
            'warnings' => 'array',
            'explanation' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RecommendationRun::class, 'recommendation_run_id');
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where('eligible', true);
    }
}
