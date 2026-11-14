<?php

namespace Database\Factories;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolcanoStatus>
 */
class VolcanoStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mountain_id' => Mountain::factory(),
            'level' => VolcanoLevel::NORMAL->value,
            'recommendation' => 'Masyarakat dan pendaki tidak mendekati kawah dalam radius 1 km.',
            'report_url' => 'https://magma.esdm.go.id/v1/gunung-api/laporan',
            'issued_at' => now()->subHours(6),
            'verified_at' => now()->subHour(),
        ];
    }
}
