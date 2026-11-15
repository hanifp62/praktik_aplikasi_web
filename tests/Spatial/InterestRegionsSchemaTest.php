<?php

namespace Tests\Spatial;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Wilayah minat di PostgreSQL (R-024, DK-42): kolom JSON yang boleh kosong, dan larik kode yang
 * tersimpan sebagai larik JSON sungguhan, bukan teks. SQLite menyimpan JSON sebagai teks, jadi
 * suite SQLite tidak membuktikan bentuk ini.
 */
class InterestRegionsSchemaTest extends SpatialTestCase
{
    public function test_the_column_holds_a_json_array_and_may_be_empty(): void
    {
        $kolom = DB::selectOne(
            "SELECT data_type, is_nullable FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'user_preferences' AND column_name = 'interest_regions'"
        );

        $this->assertSame(['json', 'YES'], [$kolom->data_type, $kolom->is_nullable]);

        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'interest_regions' => ['JAWA_TIMUR', 'SULAWESI']]);

        $this->assertSame('array', DB::selectOne('SELECT json_typeof(interest_regions) AS jenis FROM user_preferences WHERE user_id = ?', [$user->id])->jenis);
        $this->assertSame(['JAWA_TIMUR', 'SULAWESI'], $user->fresh()->preference->interest_regions);
    }
}
