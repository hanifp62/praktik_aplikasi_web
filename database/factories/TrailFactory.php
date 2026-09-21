<?php

namespace Database\Factories;

use App\Enums\NavigationComplexity;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Enums\TechnicalDemand;
use App\Enums\WaterAvailability;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TrailFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Jalur '.fake()->unique()->lastName();

        return [
            'mountain_id' => Mountain::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'distance_km' => 8,
            'elevation_gain_m' => 800,
            'elevation_loss_m' => 800,
            'estimated_duration_minutes' => 480,
            'technical_demand' => TechnicalDemand::MODERATE->value,
            'navigation_complexity' => NavigationComplexity::MODERATE->value,
            'water_availability' => WaterAvailability::LIMITED->value,
            'terrain_character' => ['FOREST'],
            'camping_available' => true,
            'starting_point' => 'Basecamp',
            'weather_adm4_code' => '33.08.10.2001',
            'weather_reference_area' => 'Area basecamp',
            'is_published' => false,
        ];
    }

    /**
     * An easy, short day-hike route.
     */
    public function easy(): static
    {
        return $this->state([
            'distance_km' => 5,
            'elevation_gain_m' => 400,
            'estimated_duration_minutes' => 300,
            'technical_demand' => TechnicalDemand::LOW->value,
            'navigation_complexity' => NavigationComplexity::LOW->value,
            'terrain_character' => ['FOREST'],
        ]);
    }

    /**
     * A long, highly technical multi-day route.
     */
    public function highlyTechnical(): static
    {
        return $this->state([
            'distance_km' => 25,
            'elevation_gain_m' => 2000,
            'estimated_duration_minutes' => 1800,
            'technical_demand' => TechnicalDemand::VERY_HIGH->value,
            'navigation_complexity' => NavigationComplexity::HIGH->value,
            'terrain_character' => ['SCREE', 'EXPOSED_RIDGE', 'STEEP_SLOPE'],
        ]);
    }

    /**
     * Jalur yang benar-benar melewati gerbang publikasi.
     *
     * Ia tidak menyalakan kolomnya langsung. Ia melengkapi sumber, satu checkpoint
     * sementara, dan status resmi lebih dulu, menerbitkan lewat jalur yang sama dengan
     * admin, lalu membuang checkpoint sementaranya. Itu bukan tipuan: gerbangnya memang
     * berlaku pada saat transisi, dan apa yang terjadi pada data sesudahnya adalah
     * urusan penandaan di daftar admin, bukan urusan gerbang. Membuangnya membuat test
     * bebas menyusun checkpointnya sendiri tanpa bentrok urutan.
     *
     * Statusnya sengaja UNKNOWN, bukan OPEN, supaya resolusi status tidak berubah bagi
     * test yang sudah ada.
     */
    public function published(): static
    {
        return $this->afterCreating(function (Trail $trail): void {
            if ($trail->data_source_id === null) {
                $trail->forceFill(['data_source_id' => DataSource::factory()->create()->id])->saveQuietly();
            }

            if ($trail->checkpoints()->count() === 0) {
                Checkpoint::factory()->for($trail)->create(['sequence' => 32000]);
            }

            if ($trail->officialStatuses()->count() === 0) {
                OfficialStatus::factory()->create([
                    'statusable_type' => $trail->getMorphClass(),
                    'statusable_id' => $trail->getKey(),
                    'scope' => StatusScope::TRAIL->value,
                    'status' => OfficialStatusValue::UNKNOWN->value,
                ]);
            }

            $trail->refresh()->update(['is_published' => true]);
        });
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
