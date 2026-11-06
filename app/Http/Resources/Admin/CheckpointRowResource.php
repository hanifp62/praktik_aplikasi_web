<?php

namespace App\Http\Resources\Admin;

use App\Models\Checkpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris daftar checkpoint pada halaman kelola checkpoint.
 *
 * @mixin Checkpoint
 */
class CheckpointRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->sequence.'. '.$this->name,
            'type' => $this->checkpoint_type->label(),
            'elevation' => $this->elevation_m
                ? number_format($this->elevation_m, 0, ',', '.').' mdpl'
                : null,
            'isian' => [
                'name' => $this->name,
                'sequence' => (string) $this->sequence,
                'checkpoint_type' => $this->checkpoint_type->value,
                'elevation_m' => $this->elevation_m !== null ? (string) $this->elevation_m : '',
                'notes' => $this->notes ?? '',
                // Dibaca dari kolom biasa; tidak perlu SQL spasial hanya untuk mengisi form.
                'latitude' => $this->latitude !== null ? (string) $this->latitude : '',
                'longitude' => $this->longitude !== null ? (string) $this->longitude : '',
            ],
        ];
    }
}
