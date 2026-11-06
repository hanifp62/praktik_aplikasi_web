<?php

namespace App\Http\Resources\Api;

use App\Models\Mountain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Gunung di API baca publik: hanya gunung yang punya sedikitnya satu jalur terbit. Gunung dan
 * jalur adalah entitas berbeda (R-001), jadi data jalur hanya ikut lewat relasi `trails` yang
 * sudah disaring terbit.
 *
 * @mixin Mountain
 */
class MountainResource extends JsonResource
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
            'province' => $this->province,
            'region' => $this->region,
            'elevation_mdpl' => $this->elevation_mdpl,
            'published_trails_count' => $this->whenCounted('trails'),
            'trails' => TrailResource::collection($this->whenLoaded('trails')),
        ];
    }
}
