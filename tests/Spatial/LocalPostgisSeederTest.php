<?php

namespace Tests\Spatial;

use App\Enums\AuthorityMemberRole;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Database\Seeders\LocalPostgisSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Pembuktian empiris `LocalPostgisSeeder` di PostgreSQL + PostGIS sungguhan (R-024).
 */
class LocalPostgisSeederTest extends SpatialTestCase
{
    public function test_local_postgis_seeder_populates_published_trails_with_geometries_and_is_idempotent(): void
    {
        $this->seed(LocalPostgisSeeder::class);

        $terbit = Trail::where('is_published', true)->get();

        $this->assertGreaterThanOrEqual(15, $terbit->count());

        foreach ($terbit as $jalur) {
            $this->assertSame([], $jalur->publishabilityReport(), "Jalur terbit {$jalur->slug} harus memenuhi seluruh syarat publikasi.");

            $geojson = $jalur->readGeoJson('geometry');
            $this->assertNotNull($geojson, "Jalur {$jalur->slug} wajib memiliki geometri LineString PostGIS.");
            $this->assertSame('LineString', $geojson['type']);
            $this->assertGreaterThanOrEqual(2, count($geojson['coordinates']));
        }

        $cibodas = Trail::where('slug', 'gede-via-cibodas')->firstOrFail();
        $jumlahPosBerkoordinat = DB::table('checkpoints')
            ->where('trail_id', $cibodas->id)
            ->whereNotNull('location')
            ->count();
        $this->assertSame(6, $jumlahPosBerkoordinat);

        $budi = User::where('email', LocalPostgisSeeder::EMAIL_PENDAKI)->firstOrFail();
        $this->assertGreaterThanOrEqual(4, $budi->tripPlans()->count());
        $this->assertGreaterThanOrEqual(4, $budi->hikingHistory()->count());

        $pendakiContoh = User::where('email', 'pendaki@example.com')->firstOrFail();
        $this->assertGreaterThanOrEqual(4, $pendakiContoh->tripPlans()->count());

        $kurator = User::where('email', LocalPostgisSeeder::EMAIL_KURATOR)->firstOrFail();
        $this->assertTrue(
            AuthorityMember::where('user_id', $kurator->id)
                ->where('role', AuthorityMemberRole::KURATOR->value)
                ->exists()
        );

        $jumlahJalurSebelum = Trail::count();
        $jumlahGunungSebelum = Mountain::count();
        $jumlahPenggunaSebelum = User::count();

        // Menjalankan ulang seeder di basis data yang sama tidak menggandakan data atau gagal pada constraint unik.
        $this->seed(LocalPostgisSeeder::class);

        $this->assertSame($jumlahJalurSebelum, Trail::count());
        $this->assertSame($jumlahGunungSebelum, Mountain::count());
        $this->assertSame($jumlahPenggunaSebelum, User::count());
    }
}
