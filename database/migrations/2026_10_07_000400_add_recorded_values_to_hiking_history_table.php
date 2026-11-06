<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Angka pendakian yang ditulis pendaki lewat "Ubah angka" (spec v3 bagian 6.13, ruling W6-R13).
 *
 * Tanpa isian, ringkasan menurunkan angkanya dari rekaman jejak atau data jalur. Isian di sini
 * menggantikan turunan itu dan ditandai waktu ubahnya. Null berarti tidak ditulis, bukan nol
 * (R-007). Aditif dan boleh kosong untuk baris lama. Migrasi rintisan
 * 2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table tidak disentuh:
 * kolom asal catatan (FR-25) tetap milik W7, yang memakai kolom angka di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_history', function (Blueprint $table) {
            $table->unsignedInteger('recorded_distance_m')->nullable()->after('personal_notes');
            $table->unsignedInteger('recorded_gain_m')->nullable()->after('recorded_distance_m');
            $table->unsignedInteger('recorded_moving_minutes')->nullable()->after('recorded_gain_m');
            $table->unsignedInteger('recorded_highest_m')->nullable()->after('recorded_moving_minutes');
            $table->timestamp('numbers_edited_at')->nullable()->after('recorded_highest_m');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_history', function (Blueprint $table) {
            $table->dropColumn(['recorded_distance_m', 'recorded_gain_m', 'recorded_moving_minutes', 'recorded_highest_m', 'numbers_edited_at']);
        });
    }
};
