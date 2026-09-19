<?php

namespace Tests\Unit;

use App\Enums\CompatibilityFactor;
use App\Enums\RouteFitLabel;
use App\Services\RouteFit\FactorScore;
use App\Services\RouteFitService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Nilai domain harus dapat dikalibrasi tim lewat config/hiking.php, bukan konstanta kelas
 * yang tersebar. Test ini menahan agar nilainya tidak kembali ditanam di dalam kode.
 */
class HikingConfigTest extends TestCase
{
    public function test_route_fit_thresholds_come_from_config(): void
    {
        config()->set('hiking.route_fit.label_threshold_fit', 0.99);

        $label = $this->invokeLabel(0.90, [
            new FactorScore(CompatibilityFactor::EXPERIENCE_MATCH, 0.90, 0.30, 'cukup'),
        ]);

        $this->assertSame(
            RouteFitLabel::PERLU_PERSIAPAN,
            $label,
            'Menaikkan ambang di config harus menurunkan label, bukan diabaikan.'
        );
    }

    public function test_critical_factor_floor_comes_from_config(): void
    {
        config()->set('hiking.route_fit.critical_factor_floor', 0.90);

        $label = $this->invokeLabel(0.95, [
            new FactorScore(CompatibilityFactor::TECHNICAL_MATCH, 0.80, 0.15, 'kurang'),
        ]);

        $this->assertSame(
            RouteFitLabel::KURANG_COCOK,
            $label,
            'Faktor kritis di bawah ambang config harus memaksa KURANG COCOK.'
        );
    }

    public function test_strong_factor_threshold_comes_from_config(): void
    {
        $factor = new FactorScore(CompatibilityFactor::TERRAIN_MATCH, 0.80, 0.15, 'kuat');

        config()->set('hiking.route_fit.strong_factor_threshold', 0.75);
        $this->assertTrue($factor->isStrong());

        config()->set('hiking.route_fit.strong_factor_threshold', 0.85);
        $this->assertFalse($factor->isStrong(), 'isStrong() harus mengikuti config, bukan angka tetap.');
    }

    public function test_elevation_reference_comes_from_config(): void
    {
        $this->assertSame(
            [1 => 600, 2 => 1000, 3 => 1600, 4 => 2200],
            config('hiking.route_fit.elevation_reference')
        );
    }

    public function test_hike_mode_and_cache_values_are_configurable(): void
    {
        $this->assertSame(75, config('hiking.hike_mode.checkpoint_arrival_radius_m'));
        $this->assertSame(6371000, config('hiking.hike_mode.earth_radius_m'));
        $this->assertIsInt(config('hiking.cache.public_ttl_seconds'));
        $this->assertNotEmpty(config('hiking.map.attribution'));
    }

    /**
     * @param  array<int, FactorScore>  $factors
     */
    private function invokeLabel(float $score, array $factors): RouteFitLabel
    {
        $service = app(RouteFitService::class);
        $method = new ReflectionMethod($service, 'label');

        return $method->invoke($service, $score, $factors);
    }
}
