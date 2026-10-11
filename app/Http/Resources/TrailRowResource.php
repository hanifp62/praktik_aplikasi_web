<?php

namespace App\Http\Resources;

use App\Enums\RouteFitLabel;
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
     * Sejak tinjauan akhir W3 (W3-C8, ruling W3-F3) baris ini tidak lagi mengirim `technical`,
     * `curam`, dan `kemudahan`: satu-satunya yang merendernya, TrailRow.tsx, sudah dihapus, dan
     * kartu Jelajah serta Simpanan memakai tiga angka (prototype 02 dan 06). Tuntutan teknis
     * tetap dibaca saringan Jelajah dari model, bukan dari baris ini.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $jarak = $this->distance_km !== null ? (float) $this->distance_km : null;
        $naik = $this->elevation_gain_m;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'url' => route('trails.show', $this->resource),
            'mountain' => $this->mountain?->name,
            'province' => $this->mountain?->province,
            'durasi' => Durasi::pendek($this->estimated_duration_minutes),
            'jarak' => $jarak !== null ? number_format($jarak, 1, ',', '.').' km' : '-',
            'tanjakan' => $naik !== null ? number_format($naik, 0, ',', '.').' m' : '-',
            'ditimbang' => $this->ditimbang,
            'fit' => $this->fit === null ? null : [
                'label' => $this->fit->label?->value,
                'labelText' => RouteFitLabel::textFor($this->fit->label),
                'alasan' => $this->fit->alasan,
                'denganRencana' => $this->fit->denganRencana,
                // Lolos syarat dasar. Bila tidak, labelnya null (kata UNKNOWN "Belum dinilai")
                // dan kartunya menulis alasannya, jadi tidak terbaca sebagai nilai rendah (R-006, R-008).
                'layak' => $this->fit->eligible,
                'mengapaSesuai' => $this->fit->mengapaSesuai,
                'perluDiperhatikan' => $this->fit->perluDiperhatikan,
            ],
        ];
    }
}
