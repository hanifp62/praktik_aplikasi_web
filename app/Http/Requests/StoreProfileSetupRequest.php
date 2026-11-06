<?php

namespace App\Http\Requests;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreferredChallenge;
use App\Enums\PreferredDuration;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * FR-02 profil pendaki. Aturannya sama persis dengan komponen Livewire yang digantikan:
 * pengalaman yang diisi sendiri adalah masukan rekomendasi, bukan sertifikasi (PRD §22).
 *
 * Isian `langkah` (1 atau 2) meminta pemeriksaan satu langkah saja, seperti nextStep()
 * dulu: pesan galatnya datang dari server dalam bahasa Indonesia sebelum pendaki pindah
 * langkah. Tanpa `langkah`, atau langkah 3, seluruh aturan berlaku dan profil disimpan.
 */
class StoreProfileSetupRequest extends FormRequest
{
    /** @var array<int, array<int, string>> */
    private const ISIAN_LANGKAH = [
        1 => ['experience_level', 'region_preference', 'bio'],
        2 => [
            'completed_hikes_count', 'terrain_experience', 'terrain_experience.*',
            'navigation_experience', 'longest_hike_duration_minutes', 'highest_elevation_gain_m',
        ],
    ];

    /**
     * Langkah yang hanya diperiksa, bukan disimpan; null berarti simpan.
     */
    public function langkahDiperiksa(): ?int
    {
        $langkah = (int) $this->input('langkah', 3);

        return array_key_exists($langkah, self::ISIAN_LANGKAH) ? $langkah : null;
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $semua = [
            'experience_level' => ['required', new Enum(ExperienceLevel::class)],
            'region_preference' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'completed_hikes_count' => ['required', 'integer', 'min:0', 'max:2000'],
            'terrain_experience' => ['array'],
            'terrain_experience.*' => [Rule::enum(TerrainCharacter::class)],
            'navigation_experience' => ['required', new Enum(NavigationExperience::class)],
            'longest_hike_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:20160'],
            'highest_elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'preferred_duration' => ['required', new Enum(PreferredDuration::class)],
            'preferred_trip_type' => ['required', new Enum(TripType::class)],
            'preferred_challenge' => ['nullable', new Enum(PreferredChallenge::class)],
            'max_elevation_gain_preference_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
        ];

        $langkah = $this->langkahDiperiksa();

        return $langkah === null
            ? $semua
            : array_intersect_key($semua, array_flip(self::ISIAN_LANGKAH[$langkah]));
    }

    /**
     * Isian kosong dari formulir React datang sebagai string kosong; aturan nullable
     * dan integer membacanya sebagai nilai, bukan ketiadaan.
     */
    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => $v === '' ? null : $v;

        $this->merge([
            'region_preference' => $kosong($this->input('region_preference')),
            'bio' => $kosong($this->input('bio')),
            'longest_hike_duration_minutes' => $kosong($this->input('longest_hike_duration_minutes')),
            'highest_elevation_gain_m' => $kosong($this->input('highest_elevation_gain_m')),
            'preferred_challenge' => $kosong($this->input('preferred_challenge')),
            'max_elevation_gain_preference_m' => $kosong($this->input('max_elevation_gain_preference_m')),
            'terrain_experience' => array_values((array) $this->input('terrain_experience', [])),
        ]);
    }
}
