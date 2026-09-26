<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detail rencana (features/04 "Rencana Perjalanan", DK-07) dan sakelar pengingat (FR-24).
 *
 * Tanggal selesai dan jumlah peserta boleh kosong. Kosong berarti pendaki belum
 * menyebutnya, bukan "satu hari" atau "nol orang" (R-007), jadi keduanya nullable tanpa
 * nilai bawaan.
 *
 * Pengingat mati sampai pemiliknya menyalakan, mengikuti pola perekaman jejak: surel
 * adalah kontak keluar dari aplikasi, dan kontak keluar dimulai atas keputusan pendaki.
 * Kolom ini aditif dan bernilai bawaan, jadi aman untuk baris yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('planned_date');
            $table->unsignedTinyInteger('participants_count')->nullable()->after('start_time');
            $table->boolean('reminders_enabled')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'participants_count', 'reminders_enabled']);
        });
    }
};
