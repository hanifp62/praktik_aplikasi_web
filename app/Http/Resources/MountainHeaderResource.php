<?php

namespace App\Http\Resources;

use App\Models\Mountain;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Identitas gunung di kepala halaman gunung: nama, provinsi, zona waktu, dan ketinggian
 * puncak, sudah diformat di server.
 *
 * Sama dengan TrailHeaderResource: ketinggian puncak hanya keterangan identitas, bukan
 * ukuran kesulitan (BR-02), jadi disusun di lapisan presentasi ini dan tidak pernah di
 * app/Services yang dijaga LockedBusinessRulesTest dari MDPL.
 *
 * @mixin Mountain
 */
class MountainHeaderResource extends JsonResource
{
    public function __construct(Mountain $mountain)
    {
        parent::__construct($mountain);
    }

    /**
     * @return array{id: int, nama: string, provinsi: ?string, puncak: ?string, zona: string}
     */
    public function toArray(Request $request): array
    {
        $mdpl = $this->elevation_mdpl;

        return [
            'id' => $this->id,
            'nama' => $this->name,
            'provinsi' => $this->province,
            'puncak' => $mdpl !== null ? number_format((int) $mdpl, 0, ',', '.').' mdpl' : null,
            'zona' => Timezone::label(Timezone::isSupported($this->timezone) ? $this->timezone : Timezone::DEFAULT),
        ];
    }
}
