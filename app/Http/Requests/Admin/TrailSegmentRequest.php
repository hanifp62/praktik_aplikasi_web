<?php

namespace App\Http\Requests\Admin;

use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Models\Trail;
use App\Models\TrailSegment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Isian segmen jalur (GAP-13, FR-16, PRD §92). Otorisasinya mengikuti TrailPolicy::update,
 * sehingga admin dan pemandu bersertifikat jenjang Ahli di kawasannya tunduk pada batas yang sama.
 */
class TrailSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trail = $this->route('trail');

        return $trail instanceof Trail && $this->user()->can('update', $trail);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $trail = $this->route('trail');
        $segment = $this->route('segment');

        return [
            'name' => ['required', 'string', 'max:120'],
            'sequence' => [
                'required', 'integer', 'min:1', 'max:200',
                Rule::unique('trail_segments', 'sequence')
                    ->where('trail_id', $trail instanceof Trail ? $trail->getKey() : null)
                    ->ignore($segment instanceof TrailSegment ? $segment->getKey() : null),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'technical_demand' => ['nullable', new Enum(TechnicalDemand::class)],
            'terrain_character' => ['nullable', 'array', 'max:10'],
            'terrain_character.*' => [new Enum(TerrainCharacter::class)],
        ];
    }
}
