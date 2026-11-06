<?php

namespace App\Http\Requests;

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
use App\Models\Trail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Data jalur dari pemandu bersertifikat untuk kawasan yang disahkan untuknya (PRD §43).
 *
 * Otorisasi mendahului validasi, sama seperti komponen Livewire yang digantikannya:
 * pemandu di luar kawasannya ditolak sebelum isiannya dibaca.
 */
class StoreTrailContributionRequest extends FormRequest
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
        return [
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
            'description' => ['nullable', 'string', 'max:2000'],

            // Wajib. Angka tanpa asal tidak dapat dinilai pembacanya (§60), dan di sini
            // asalnya adalah pengetahuan lapangan seseorang, bukan dokumen yang dapat
            // ditelusuri sendiri oleh pembaca.
            'catatan_sumber' => ['required', 'string', 'min:15', 'max:500'],
        ];
    }
}
