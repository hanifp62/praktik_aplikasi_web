<?php

namespace Tests\Spatial;

use Illuminate\Support\Facades\DB;

/**
 * Kolom aditif W6 di PostgreSQL (R-024): jeda sesi mode lapangan, alasan pembatalan DK-50,
 * angka ubahan ringkasan, serta jam kembali dan nomor registrasi pesan rencana. Suite SQLite
 * tidak membuktikan jenis, panjang, nilai bawaan, dan nullability kolom seperti di produksi.
 * Migrasinya tidak dijalankan di Supabase sebelum izin pemilik (spec v3 Q2).
 */
class FieldModeColumnsSchemaTest extends SpatialTestCase
{
    public function test_the_additive_columns_have_their_production_types(): void
    {
        $kolom = collect(DB::select(
            "SELECT table_name, column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name IN ('hiking_sessions', 'trip_plans', 'hiking_history')"
        ))->keyBy(fn (object $k) => $k->table_name.'.'.$k->column_name);

        $harapan = [
            'hiking_sessions.paused_at' => ['timestamp without time zone', 'YES', null],
            'hiking_sessions.paused_seconds' => ['integer', 'NO', null],
            'hiking_sessions.last_resumed_at' => ['timestamp without time zone', 'YES', null],
            'trip_plans.cancellation_reason' => ['character varying', 'YES', 20],
            'trip_plans.expected_return_at' => ['timestamp without time zone', 'YES', null],
            'trip_plans.registration_number' => ['character varying', 'YES', 60],
            'hiking_history.recorded_distance_m' => ['integer', 'YES', null],
            'hiking_history.recorded_gain_m' => ['integer', 'YES', null],
            'hiking_history.recorded_moving_minutes' => ['integer', 'YES', null],
            'hiking_history.recorded_highest_m' => ['integer', 'YES', null],
            'hiking_history.numbers_edited_at' => ['timestamp without time zone', 'YES', null],
        ];

        foreach ($harapan as $nama => [$jenis, $boleh, $panjang]) {
            $this->assertTrue($kolom->has($nama), "Kolom {$nama} tidak ada.");
            $this->assertSame($jenis, $kolom[$nama]->data_type, $nama);
            $this->assertSame($boleh, $kolom[$nama]->is_nullable, $nama);
            $this->assertSame($panjang, $kolom[$nama]->character_maximum_length === null ? null : (int) $kolom[$nama]->character_maximum_length, $nama);
        }

        // Jeda yang belum pernah terjadi bernilai nol detik, bukan kosong, untuk baris lama juga.
        $this->assertSame('0', $kolom['hiking_sessions.paused_seconds']->column_default);
    }
}
