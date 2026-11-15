<?php

namespace Tests\Spatial;

use App\Models\Mountain;
use App\Models\MountainPhoto;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel foto gunung di PostgreSQL (R-024; DK-37; ruling W8-R19): satu foto per gunung, lisensi dari
 * daftar tertutup, sidik SHA-256 heksadesimal, foto ikut terhapus bersama gunungnya, pengunggah
 * menjadi kosong, dan RLS menutup REST API.
 */
class MountainPhotoSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'mountain_photos'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'mountain_photos'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('mountain_photos_lisensi_sah', $cek);
        $this->assertContains('mountain_photos_sha256_sah', $cek);

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'mountain_photos'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['mountain_id']);
        $this->assertSame('n', $hapus['uploaded_by']);
    }

    public function test_one_photo_per_mountain(): void
    {
        $gunung = Mountain::factory()->create();
        MountainPhoto::factory()->for($gunung)->create();

        $this->expectException(UniqueConstraintViolationException::class);
        MountainPhoto::factory()->for($gunung)->create();
    }

    public function test_an_unknown_license_is_refused(): void
    {
        $foto = MountainPhoto::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('mountain_photos')->where('id', $foto->id)->update(['license' => 'ALL_RIGHTS_RESERVED']);
    }

    public function test_a_malformed_fingerprint_is_refused(): void
    {
        $foto = MountainPhoto::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('mountain_photos')->where('id', $foto->id)->update(['sha256' => str_repeat('Z', 64)]);
    }
}
