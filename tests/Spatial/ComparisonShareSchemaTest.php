<?php

namespace Tests\Spatial;

use App\Models\ComparisonShare;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\Trails\TautanBagikan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi tautan bagikan di PostgreSQL (keputusan koordinator W3 nomor 10, R-024): suite
 * SQLite tidak membuktikan RLS, indeks unik, JSON, dan cascade seperti di produksi. Migrasinya
 * tidak dijalankan di Supabase sebelum izin pemilik (spec v3 Q2).
 */
class ComparisonShareSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_row_level_security_and_a_unique_hash(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'comparison_shares'")->relrowsecurity);

        $user = User::factory()->create();
        ComparisonShare::factory()->for($user)->create(['token_hash' => str_repeat('a', 64)]);

        $this->expectException(UniqueConstraintViolationException::class);
        ComparisonShare::factory()->for($user)->create(['token_hash' => str_repeat('a', 64)]);
    }

    public function test_a_link_round_trips_and_disappears_with_its_account(): void
    {
        $user = User::factory()->create();
        $trails = new Collection([
            $this->jalurTerbit(),
            $this->jalurTerbit(),
        ]);

        [$token, $share] = app(TautanBagikan::class)->buat($user, $trails->reverse()->values());

        $this->assertTrue(app(TautanBagikan::class)->buka($token)->is($share));
        $this->assertSame($trails->reverse()->pluck('id')->values()->all(), $share->fresh()->trail_ids);

        $user->delete();

        $this->assertSame(0, ComparisonShare::count());
    }

    /**
     * forceFill()->saveQuietly() melewati gerbang publikasi dengan sengaja, pola
     * RecommendationGeometryExclusionTest (ruling W3-F6): test ini menguji skema
     * tautan bagikan (RLS, indeks unik, cascade pada hapus akun), bukan kelengkapan
     * data jalur, jadi jalur tidak perlu geometri atau checkpoint untuk tetap jujur
     * pada maksud test ini.
     */
    private function jalurTerbit(): Trail
    {
        $trail = Trail::factory()->for(Mountain::factory())->create();

        $trail->forceFill(['is_published' => true])->saveQuietly();

        return $trail->fresh();
    }
}
