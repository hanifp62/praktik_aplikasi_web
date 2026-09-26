<?php

namespace App\Http\Requests\Admin;

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
use App\Models\Trail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Isian jalur untuk simpan maupun ubah, dengan aturan yang sama seperti komponen
 * Livewire yang digantikannya. Otorisasi lewat TrailPolicy: create() untuk yang baru,
 * update() untuk yang datang lewat parameter route.
 */
class TrailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trail = $this->route('trail');

        return $trail instanceof Trail
            ? $this->user()->can('update', $trail)
            : $this->user()->can('create', Trail::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mountain_id' => ['required', 'exists:mountains,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'elevation_loss_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:20160'],
            'technical_demand' => ['required', new Enum(TechnicalDemand::class)],
            'navigation_complexity' => ['required', new Enum(NavigationComplexity::class)],
            'water_availability' => ['required', new Enum(WaterAvailability::class)],
            'terrain_character' => ['array'],
            'terrain_character.*' => [Rule::enum(TerrainCharacter::class)],
            'camping_available' => ['boolean'],
            'starting_point' => ['nullable', 'string', 'max:160'],
            'weather_adm4_code' => ['nullable', 'string', 'max:40'],
            'weather_reference_area' => ['nullable', 'string', 'max:160'],
            'data_source_id' => ['nullable', Rule::exists('data_sources', 'id')],
        ];
    }

    /**
     * Slug jalur adalah nama ditambah gunungnya dan unik di basis data. Nama kembar di
     * gunung yang sama dinyatakan di samping isian nama, bukan sebagai galat 500.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['name', 'mountain_id'])) {
                    return;
                }

                $trail = $this->route('trail');

                $kembar = Trail::query()
                    ->where('slug', Str::slug($this->input('name').'-'.$this->input('mountain_id')))
                    ->when($trail instanceof Trail, fn ($query) => $query->whereKeyNot($trail->getKey()))
                    ->exists();

                if ($kembar) {
                    $validator->errors()->add('name', 'Jalur dengan nama ini sudah ada di gunung tersebut.');
                }
            },
        ];
    }
}
