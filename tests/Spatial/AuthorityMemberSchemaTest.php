<?php

namespace Tests\Spatial;

use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel anggota otoritas di PostgreSQL (R-024; ruling W8-R7): satu keanggotaan aktif per pengguna
 * per badan (indeks unik parsial), peran dari daftar tertutup, masa berlaku yang tidak terbalik,
 * kunci asing yang ikut terhapus bersama akun dan badannya, dan RLS yang menutup REST API. Suite
 * SQLite tidak membuktikan CHECK dan RLS. Migrasinya tidak dijalankan di Supabase sebelum izin
 * pemilik (spec v3 Q2).
 */
class AuthorityMemberSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'authority_members'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'authority_members'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('authority_members_peran_sah', $cek);
        $this->assertContains('authority_members_masa_sah', $cek);

        $indeks = DB::selectOne("SELECT indexdef FROM pg_indexes WHERE tablename = 'authority_members' AND indexname = 'authority_members_aktif_unik'");
        $this->assertNotNull($indeks);
        $this->assertStringContainsString('revoked_at IS NULL', $indeks->indexdef);

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'authority_members'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['user_id']);
        $this->assertSame('c', $hapus['authority_id']);
        $this->assertSame('n', $hapus['granted_by']);
        $this->assertSame('n', $hapus['revoked_by']);
    }

    public function test_one_active_membership_per_user_per_authority(): void
    {
        $user = User::factory()->create();
        $badan = Authority::factory()->create();
        AuthorityMember::factory()->for($user)->for($badan)->create();

        $this->expectException(UniqueConstraintViolationException::class);
        AuthorityMember::factory()->for($user)->for($badan)->create();
    }

    public function test_a_revoked_membership_does_not_block_a_new_one(): void
    {
        $user = User::factory()->create();
        $badan = Authority::factory()->create();
        AuthorityMember::factory()->for($user)->for($badan)->create(['revoked_at' => now()]);
        AuthorityMember::factory()->for($user)->for($badan)->create();

        $this->assertSame(2, AuthorityMember::where('user_id', $user->id)->count());
    }

    /**
     * Satu pelanggaran per test: di PostgreSQL galat pertama membatalkan transaksi test, jadi
     * pernyataan sesudahnya gagal karena transaksinya, bukan karena CHECK-nya.
     */
    public function test_an_unknown_role_is_refused(): void
    {
        $keanggotaan = AuthorityMember::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('authority_members')->where('id', $keanggotaan->id)->update(['role' => 'ADMIN']);
    }

    public function test_a_reversed_period_is_refused(): void
    {
        $keanggotaan = AuthorityMember::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('authority_members')->where('id', $keanggotaan->id)->update(['valid_from' => '2026-10-09', 'valid_until' => '2026-10-01']);
    }
}
