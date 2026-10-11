<?php

namespace Tests\Feature;

use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Perintah memount:bersihkan-snapshot-kondisi (W4-6h; docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2,
 * 3, 5): laporan komunitas di snapshot kesiapan lama dipangkas ke kunci minimal tanpa data
 * pribadi pelapor. Hanya dijalankan di PHPUnit; menjalankannya di Supabase adalah butir izin
 * akhir 18.
 */
class BersihkanSnapshotKondisiTest extends TestCase
{
    use RefreshDatabase;

    private const PERINTAH = 'memount:bersihkan-snapshot-kondisi';

    /**
     * @param  array<int, array<string, mixed>>  $laporan
     */
    private function cek(TripPlan $trip, array $laporan): ReadinessCheck
    {
        return ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => 'NEEDS_PREPARATION',
            'official_status_snapshot' => ['status' => 'UNKNOWN'],
            'condition_snapshot' => ['weather' => ['available' => false], 'community' => ['available' => $laporan !== [], 'reports' => $laporan]],
            'pre_departure_confirmed' => true,
            'computed_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function laporanLama(): array
    {
        return [
            'id' => 7,
            'user_id' => 12,
            'trail_id' => 3,
            'created_at' => '2026-09-01T02:00:00.000000Z',
            'hike_date' => '2026-08-30T00:00:00.000000Z',
            'condition_tags' => ['SLIPPERY'],
            'note' => 'Catatan pribadi pelapor',
            'photo_path' => 'laporan/rahasia.jpg',
            'moderated_by' => 4,
            'thanks_count' => 2,
            'user' => ['id' => 12, 'name' => 'Nama Pelapor Rahasia'],
            'segment' => ['id' => 5, 'name' => 'Segmen Puncak', 'description' => 'Uraian segmen'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function laporanMinimal(): array
    {
        return [
            'id' => 7,
            'created_at' => '2026-09-01T02:00:00.000000Z',
            'hike_date' => '2026-08-30T00:00:00.000000Z',
            'condition_tags' => ['SLIPPERY'],
            'segment' => ['id' => 5, 'name' => 'Segmen Puncak'],
        ];
    }

    public function test_old_rows_lose_the_reporters_data_and_minimal_rows_are_left_alone(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 03:00:00', 'UTC'));
        $trip = TripPlan::factory()->create();
        $lama = $this->cek($trip, [self::laporanLama()]);
        $minimal = $this->cek($trip, [self::laporanMinimal()]);
        $kosong = $this->cek($trip, []);
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));

        $this->artisan(self::PERINTAH)
            ->expectsOutput('3 baris snapshot diperiksa; 1 baris dibersihkan.')
            ->doesntExpectOutputToContain('Nama Pelapor Rahasia')
            ->assertSuccessful();

        $this->assertSame([self::laporanMinimal()], $lama->fresh()->condition_snapshot['community']['reports']);
        $this->assertSame(['available' => false], $lama->fresh()->condition_snapshot['weather']);
        $this->assertSame([self::laporanMinimal()], $minimal->fresh()->condition_snapshot['community']['reports']);
        $this->assertSame([], $kosong->fresh()->condition_snapshot['community']['reports']);
        $this->assertSame('2026-10-01 03:00:00', $lama->fresh()->updated_at->toDateTimeString(), 'Waktu "Tinjauan tercatat" dibaca dari updated_at; pembersihan tidak menggesernya.');
        $this->assertStringNotContainsString('Nama Pelapor Rahasia', (string) $lama->fresh()->getRawOriginal('condition_snapshot'));
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $trip = TripPlan::factory()->create();
        $this->cek($trip, [self::laporanLama(), self::laporanLama()]);

        $this->artisan(self::PERINTAH, ['--potongan' => 1])->expectsOutput('1 baris snapshot diperiksa; 1 baris dibersihkan.')->assertSuccessful();
        $this->artisan(self::PERINTAH)->expectsOutput('1 baris snapshot diperiksa; 0 baris dibersihkan.')->assertSuccessful();
    }

    public function test_a_dry_run_only_counts(): void
    {
        $trip = TripPlan::factory()->create();
        $lama = $this->cek($trip, [self::laporanLama()]);
        $this->cek($trip, [self::laporanLama()]);

        $this->artisan(self::PERINTAH, ['--dry-run' => true, '--potongan' => 1])
            ->expectsOutput('2 baris snapshot diperiksa; 2 baris akan dibersihkan (uji coba, tidak ada yang ditulis).')
            ->assertSuccessful();

        $this->assertSame([self::laporanLama()], $lama->fresh()->condition_snapshot['community']['reports']);
    }
}
