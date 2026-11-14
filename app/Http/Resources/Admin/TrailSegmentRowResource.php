<?php

namespace App\Http\Resources\Admin;

use App\Enums\TerrainCharacter;
use App\Models\TrailSegment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris daftar segmen jalur pada halaman kelola segmen.
 *
 * @mixin TrailSegment
 */
class TrailSegmentRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $medan = array_values(array_filter(array_map(
            fn (string $nilai) => TerrainCharacter::tryFrom($nilai)?->label(),
            $this->terrain_character ?? [],
        )));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->sequence.'. '.$this->name,
            'description' => $this->description,
            'distance' => $this->distance_km !== null
                ? number_format((float) $this->distance_km, 2, ',', '.')."\u{00A0}km"
                : null,
            'elevationGain' => $this->elevation_gain_m !== null
                ? '↑'.number_format((int) $this->elevation_gain_m, 0, ',', '.')."\u{00A0}m"
                : null,
            'technicalDemand' => $this->technical_demand?->label(),
            'terrainLabels' => $medan,
            'checkpointsCount' => (int) ($this->checkpoints_count ?? 0),
            'isian' => [
                'name' => $this->name,
                'sequence' => (string) $this->sequence,
                'description' => $this->description ?? '',
                'distance_km' => $this->distance_km !== null ? (string) (float) $this->distance_km : '',
                'elevation_gain_m' => $this->elevation_gain_m !== null ? (string) $this->elevation_gain_m : '',
                'technical_demand' => $this->technical_demand?->value ?? '',
                'terrain_character' => array_values($this->terrain_character ?? []),
            ],
        ];
    }
}
