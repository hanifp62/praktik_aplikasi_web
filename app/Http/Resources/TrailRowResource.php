<?php

namespace App\Http\Resources;

use App\Enums\WaterAvailability;
use App\Models\Trail;
use App\Services\RouteFit\TrailFitSummary;
use App\Support\Durasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris jalur untuk daftar: apa yang dilihat pendaki, sudah diformat di server.
 *
 * Angka diformat di sini (koma desimal, titik ribuan, "8 j") dan bukan di React, supaya
 * tabel banding, daftar simpanan, dan Jelajah tidak punya tiga cara menulis "8,0 km".
 * Skor internal tidak pernah ikut (BR-09): hanya label publik, alasan, dan kelayakan.
 *
 * @mixin Trail
 */
class TrailRowResource extends JsonResource
{
    public function __construct(Trail $trail, private ?TrailFitSummary $fit = null, private bool $ditimbang = false)
    {
        parent::__construct($trail);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $jarak = $this->distance_km !== null ? (float) $this->distance_km : null;
        $naik = $this->elevation_gain_m;

        // Kecuraman hanya ketika kedua angkanya ada: membagi dengan ketiadaan
        // menghasilkan angka yang terlihat pasti tanpa dasar (PRD §91).
        $curam = ($jarak && $naik) ? (int) round($naik / $jarak) : null;

        $kemudahan = array_values(array_filter([
            $this->camping_available ? 'Bisa berkemah' : null,
            $this->water_availability !== WaterAvailability::UNKNOWN ? $this->water_availability?->label() : null,
        ]));

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'url' => route('trails.show', $this->resource),
            'mountain' => $this->mountain?->name,
            'province' => $this->mountain?->province,
            'technical' => $this->technical_demand?->label(),
            'durasi' => Durasi::pendek($this->estimated_duration_minutes),
            'jarak' => $jarak !== null ? number_format($jarak, 1, ',', '.').' km' : '-',
            'tanjakan' => $naik !== null ? number_format($naik, 0, ',', '.').' m' : '-',
            'curam' => $curam !== null ? number_format($curam, 0, ',', '.').' m/km' : null,
            'kemudahan' => $kemudahan,
            'ditimbang' => $this->ditimbang,
            'fit' => $this->fit === null ? null : [
                'label' => $this->fit->label?->value,
                'labelText' => $this->fit->label?->label() ?? 'Tidak dinilai',
                'alasan' => $this->fit->alasan,
                'denganRencana' => $this->fit->denganRencana,
            ],
        ];
    }
}
