<?php

namespace App\Http\Resources;

use App\Models\Trail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Identitas jalur di kepala halaman rinci dan halaman menunggu: nama, gunung, provinsi,
 * dan ketinggian puncak, sudah diformat di server.
 *
 * Ketinggian puncak di sini hanya keterangan identitas gunung, bukan ukuran kesulitan
 * (BR-02). Karena itu ia disusun di lapisan presentasi ini, bukan di app/Services yang
 * menjadi lapisan penilaian dan dijaga LockedBusinessRulesTest dari MDPL.
 *
 * @mixin Trail
 */
class TrailHeaderResource extends JsonResource
{
    public function __construct(Trail $trail)
    {
        parent::__construct($trail);
    }

    /**
     * @return array{id: int, name: string, description: ?string, mountain: string, mountainId: int, province: ?string, mdpl: ?string}
     */
    public function toArray(Request $request): array
    {
        $mdpl = $this->mountain->elevation_mdpl;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'mountain' => $this->mountain->name,
            'mountainId' => $this->mountain->id,
            'province' => $this->mountain->province,
            'mdpl' => $mdpl !== null ? number_format((int) $mdpl, 0, ',', '.').' mdpl' : null,
        ];
    }
}
