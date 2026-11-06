<?php

namespace App\Http\Resources\Api;

use App\Models\Checkpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pos jalur di API baca publik, hanya milik jalur terbit. Pos adalah titik resmi jalur, bukan data
 * pendaki, jadi koordinatnya boleh ikut; yang belum dicatat tetap null (R-007).
 *
 * @mixin Checkpoint
 */
class CheckpointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trail_id' => $this->trail_id,
            'sequence' => $this->sequence,
            'name' => $this->name,
            'checkpoint_type' => $this->checkpoint_type?->value,
            'elevation_m' => $this->elevation_m,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
