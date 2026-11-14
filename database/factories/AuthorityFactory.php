<?php

namespace Database\Factories;

use App\Enums\AuthorityType;
use App\Models\Authority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Authority>
 */
class AuthorityFactory extends Factory
{
    public function definition(): array
    {
        // Tanpa apostrof, alasan yang sama dengan TrailFactory.
        $nama = 'Balai '.str_replace("'", '', fake()->unique()->lastName());

        return [
            'name' => $nama,
            'slug' => Str::slug($nama).'-'.fake()->unique()->numberBetween(1, 99999),
            'type' => AuthorityType::NATIONAL_PARK->value,
            'website' => 'https://contoh.go.id',
            'verified_at' => now(),
        ];
    }
}
