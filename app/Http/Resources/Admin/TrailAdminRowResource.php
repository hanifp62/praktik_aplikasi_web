<?php

namespace App\Http\Resources\Admin;

use App\Models\Trail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris daftar jalur di halaman admin.
 *
 * `kurang` adalah publishabilityReport() apa adanya (PRD §110): kurator harus tahu apa
 * yang kurang, bukan sekadar ditolak. Daftar itu ikut dikirim untuk jalur yang sudah
 * tayang, karena gerbangnya dahulu hanya berjalan saat tombol publikasi ditekan dan
 * baris lama bisa telanjur tayang dalam keadaan tidak lengkap.
 *
 * @mixin Trail
 */
class TrailAdminRowResource extends JsonResource
{
    /**
     * Memerlukan with('mountain') dan withPublishabilityData() pada query pemanggil,
     * supaya daftar ini tidak menambah query per jalur (PageQueryBudgetTest).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mountain' => $this->mountain?->name ?? '-',
            'checkpoints' => number_format((int) $this->checkpoints_count, 0, ',', '.'),
            'publication' => $this->is_published ? 'Tayang' : 'Draft',
            'published' => (bool) $this->is_published,
            'archived' => $this->archived_at !== null,
            'kurang' => $this->publishabilityReport(),
            'tautan' => [
                'checkpoint' => route('trails.checkpoints', $this->resource),
                'geometri' => route('trails.geometry', $this->resource),
            ],
            'isian' => [
                'mountain_id' => (string) $this->mountain_id,
                'name' => $this->name,
                'description' => $this->description ?? '',
                'distance_km' => $this->distance_km !== null ? (string) (float) $this->distance_km : '',
                'elevation_gain_m' => $this->elevation_gain_m !== null ? (string) $this->elevation_gain_m : '',
                'elevation_loss_m' => $this->elevation_loss_m !== null ? (string) $this->elevation_loss_m : '',
                'estimated_duration_minutes' => $this->estimated_duration_minutes !== null ? (string) $this->estimated_duration_minutes : '',
                'technical_demand' => $this->technical_demand->value,
                'navigation_complexity' => $this->navigation_complexity->value,
                'water_availability' => $this->water_availability->value,
                'terrain_character' => array_values($this->terrain_character ?? []),
                'camping_available' => (bool) $this->camping_available,
                'starting_point' => $this->starting_point ?? '',
                'weather_adm4_code' => $this->weather_adm4_code ?? '',
                'weather_reference_area' => $this->weather_reference_area ?? '',
                'data_source_id' => $this->data_source_id !== null ? (string) $this->data_source_id : '',
            ],
        ];
    }
}
