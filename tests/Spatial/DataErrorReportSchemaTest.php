<?php

namespace Tests\Spatial;

use App\Models\DataErrorReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel laporan kesalahan data di PostgreSQL (R-024; ruling W8-R17): keadaan dan bagian dari daftar
 * tertutup, laporan ikut terhapus bersama akun pelapor dan gunungnya, pemeriksa dan catatan status
 * menjadi kosong bila rujukannya hilang, indeks antrean ada, dan RLS menutup REST API. Suite SQLite
 * tidak membuktikan CHECK dan RLS. Migrasinya tidak dijalankan di Supabase sebelum izin pemilik.
 */
class DataErrorReportSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'data_error_reports'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'data_error_reports'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('data_error_reports_keadaan_sah', $cek);
        $this->assertContains('data_error_reports_bagian_sah', $cek);

        $this->assertNotNull(DB::selectOne("SELECT 1 AS ada FROM pg_indexes WHERE tablename = 'data_error_reports' AND indexname = 'data_error_reports_antrean'"));

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'data_error_reports'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['user_id']);
        $this->assertSame('c', $hapus['mountain_id']);
        $this->assertSame('c', $hapus['trail_id']);
        $this->assertSame('n', $hapus['official_status_id']);
        $this->assertSame('n', $hapus['checked_by']);
    }

    public function test_an_unknown_state_is_refused(): void
    {
        $laporan = DataErrorReport::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('data_error_reports')->where('id', $laporan->id)->update(['state' => 'ACCEPTED']);
    }

    public function test_an_unknown_section_is_refused(): void
    {
        $laporan = DataErrorReport::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('data_error_reports')->where('id', $laporan->id)->update(['category' => 'WEATHER']);
    }
}
