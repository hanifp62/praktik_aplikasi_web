<?php

namespace App\Http\Resources\Admin;

use App\Models\Mountain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris daftar gunung di halaman admin: teks siap tampil, ditambah nilai mentah
 * untuk mengisi formulir ubah.
 *
 * @mixin Mountain
 */
class MountainRowResource extends JsonResource
{
    /**
     * Memerlukan withCount('trails') pada query pemanggil.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'province' => $this->province ?? '-',
            'elevation' => $this->elevation_mdpl !== null
                ? number_format($this->elevation_mdpl, 0, ',', '.').' mdpl'
                : '-',
            'trails' => number_format((int) $this->trails_count, 0, ',', '.'),
            'status' => $this->isArchived() ? 'Diarsipkan' : 'Aktif',
            'archived' => $this->isArchived(),
            'isian' => [
                'name' => $this->name,
                'province' => $this->province ?? '',
                'region' => $this->region ?? '',
                'elevation_mdpl' => $this->elevation_mdpl !== null ? (string) $this->elevation_mdpl : '',
                'description' => $this->description ?? '',
                'data_source_id' => $this->data_source_id !== null ? (string) $this->data_source_id : '',
                // Dibaca dari kolom biasa: kolom geografi kosong pada koneksi tanpa PostGIS.
                'latitude' => $this->latitude !== null ? (string) $this->latitude : '',
                'longitude' => $this->longitude !== null ? (string) $this->longitude : '',
            ],
        ];
    }
}
