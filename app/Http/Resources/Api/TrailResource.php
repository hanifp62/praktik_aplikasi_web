<?php

namespace App\Http\Resources\Api;

use App\Models\Trail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Jalur di API baca publik. Hanya jalur terbit yang sampai ke sini (R-022). Field dipilih satu per
 * satu, jadi kolom internal (sumber data, status terbit, arsip) tidak ikut. Nilai yang belum
 * diketahui tetap null, tidak diganti nol (R-007).
 *
 * @mixin Trail
 */
class TrailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'mountain' => $this->whenLoaded('mountain', fn () => [
                'id' => $this->mountain->id,
                'name' => $this->mountain->name,
                'province' => $this->mountain->province,
            ]),
            'distance_km' => $this->distance_km !== null ? (float) $this->distance_km : null,
            'elevation_gain_m' => $this->elevation_gain_m,
            'elevation_loss_m' => $this->elevation_loss_m,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'technical_demand' => $this->technical_demand?->value,
            'navigation_complexity' => $this->navigation_complexity?->value,
            'starting_point' => $this->starting_point,
            'url' => route('public.trail', $this->slug),
            'checkpoints' => CheckpointResource::collection($this->whenLoaded('checkpoints')),
        ];
    }
}
