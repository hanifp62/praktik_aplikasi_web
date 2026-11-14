<?php

namespace Tests\Spatial;

use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Perlengkapan pribadi di PostgreSQL (R-024, ruling W7-R8): kolom yang diisi migrasi aditif di
 * atas rintisan 2026_09_25_000921, indeks unik ekspresi lower(name) per pendaki, kunci asing yang
 * ikut terhapus bersama akun, RLS yang menutup REST API, dan tautan butir persiapan yang dilepas
 * (bukan dihapus) ketika barangnya dihapus. Suite SQLite tidak membuktikan perilaku PostgreSQL
 * untuk satu pun dari itu. Migrasinya tidak dijalankan di Supabase sebelum izin pemilik.
 */
class UserOwnedItemSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'user_owned_items'"
        ))->keyBy('column_name');

        $this->assertSame(['bigint', 'NO'], [$kolom['user_id']->data_type, $kolom['user_id']->is_nullable]);
        $this->assertSame(['character varying', 'NO', 60], [$kolom['name']->data_type, $kolom['name']->is_nullable, (int) $kolom['name']->character_maximum_length]);
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'user_owned_items'")->relrowsecurity, 'RLS lapis kedua harus menyala.');
        $this->assertSame('c', DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conrelid = 'user_owned_items'::regclass AND contype = 'f'")->confdeltype, 'Barang ikut terhapus bersama akunnya.');
        $this->assertSame('n', DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conrelid = 'trip_preparation_items'::regclass AND contype = 'f' AND conname LIKE '%user_owned_item_id%'")->confdeltype, 'Butir trip tetap ada; tautannya menjadi null.');
    }

    public function test_one_name_per_hiker_regardless_of_case(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->create(['name' => 'Headlamp']);
        User::factory()->create()->ownedItems()->create(['name' => 'headlamp']);

        $this->expectException(UniqueConstraintViolationException::class);
        $user->ownedItems()->create(['name' => 'HEADLAMP']);
    }

    public function test_deleting_an_item_unlinks_trip_items_and_deleting_the_account_removes_items(): void
    {
        $user = User::factory()->create();
        $barang = $user->ownedItems()->create(['name' => 'Gaiter']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $butir = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'user_owned_item_id' => $barang->id,
            'category' => 'EQUIPMENT',
            'label' => 'Gaiter',
            'is_critical' => false,
            'status' => 'NOT_CONFIRMED',
        ]);

        $barang->delete();
        $this->assertNull($butir->fresh()->user_owned_item_id);

        $user->ownedItems()->create(['name' => 'Matras']);
        $user->delete();
        $this->assertSame(0, (int) DB::selectOne('SELECT count(*) AS n FROM user_owned_items')->n);
    }
}
