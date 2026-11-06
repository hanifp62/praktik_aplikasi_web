<?php

namespace Tests\Unit;

use App\Services\Trips\PenerimaanJejak;
use PHPUnit\Framework\TestCase;

/**
 * Kunci detik UTC titik jejak (ruling W6-P2c). Fungsi murni, diuji langsung karena kegagalan yang
 * dicegahnya hanya teramati di PostgreSQL: dua baris berkunci sama dalam satu upsert ditolak
 * seluruhnya ("ON CONFLICT DO UPDATE command cannot affect row a second time"), sedangkan SQLite
 * memprosesnya baris demi baris dan lulus (R-024; tests/Spatial/HikeTrackUpsertTest).
 */
class PenerimaanJejakTest extends TestCase
{
    /**
     * @return array{recorded_at: string, latitude: float, longitude: float, elevation_m: int|null, accuracy_m: int|null}
     */
    private function titik(string $waktu, float $lintang): array
    {
        return ['recorded_at' => $waktu, 'latitude' => $lintang, 'longitude' => 110.44, 'elevation_m' => 1200, 'accuracy_m' => 8];
    }

    public function test_points_in_the_same_utc_second_collapse_to_the_last_one_sent(): void
    {
        $hasil = PenerimaanJejak::titikPerDetik([
            $this->titik('2026-09-15T04:00:00.100Z', -7.40),
            $this->titik('2026-09-15T04:00:05.000Z', -7.41),
            $this->titik('2026-09-15T11:00:00.900+07:00', -7.42),
        ]);

        $this->assertCount(2, $hasil);
        $this->assertSame('2026-09-15 04:00:00', $hasil[0]['recorded_at']->format('Y-m-d H:i:s'));
        $this->assertSame(-7.42, $hasil[0]['latitude'], 'Titik yang terakhir dikirim untuk detik yang sama yang dipakai.');
        $this->assertSame(-7.41, $hasil[1]['latitude']);
    }

    /**
     * Fixture disusun terbalik (butir 1): urutan kiriman berlawanan dengan urutan waktu, jadi test
     * merah bila pengurutan dibuang.
     */
    public function test_points_come_back_in_time_order(): void
    {
        $hasil = PenerimaanJejak::titikPerDetik([
            $this->titik('2026-09-15T04:10:00Z', -7.43),
            $this->titik('2026-09-15T04:05:00Z', -7.42),
            $this->titik('2026-09-15T04:00:00Z', -7.41),
        ]);

        $this->assertSame(
            ['2026-09-15 04:00:00', '2026-09-15 04:05:00', '2026-09-15 04:10:00'],
            array_map(fn (array $t) => $t['recorded_at']->format('Y-m-d H:i:s'), $hasil),
        );
    }

    /**
     * ->utc() sesudah parse (butir 6): jam dinding berzona +07:00 menjadi instan UTC, dipotong ke
     * detik, sehingga kunci perangkat (toISOString dibulatkan ke detik) dan kunci server sama.
     */
    public function test_times_are_converted_to_utc_and_truncated_to_the_second(): void
    {
        $hasil = PenerimaanJejak::titikPerDetik([$this->titik('2026-09-15T11:00:07.999+07:00', -7.40)]);

        $this->assertSame('UTC', $hasil[0]['recorded_at']->getTimezone()->getName());
        $this->assertSame('2026-09-15 04:00:07.000000', $hasil[0]['recorded_at']->format('Y-m-d H:i:s.u'));
    }

    public function test_missing_optional_values_stay_null_not_zero(): void
    {
        $hasil = PenerimaanJejak::titikPerDetik([['recorded_at' => '2026-09-15T04:00:00Z', 'latitude' => -7.4, 'longitude' => 110.4]]);

        $this->assertNull($hasil[0]['elevation_m']);
        $this->assertNull($hasil[0]['accuracy_m']);
    }
}
