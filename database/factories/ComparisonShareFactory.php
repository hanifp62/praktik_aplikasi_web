<?php

namespace Database\Factories;

use App\Models\ComparisonShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComparisonShare>
 */
class ComparisonShareFactory extends Factory
{
    protected $model = ComparisonShare::class;

    /**
     * Tanpa token asli: factory hanya untuk baris yang tidak perlu dibuka lewat tautannya.
     * Test yang membuka tautan membuatnya lewat TautanBagikan::buat().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'trail_ids' => [],
            'expires_at' => now()->addDays(7),
        ];
    }
}
