<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeda dan Lanjut di mode lapangan (spec v3 bagian 6.12, ruling W6-R7).
 *
 * Waktu bergerak di ringkasan (spec v3 bagian 6.13) adalah lama sesi dikurangi jeda; tanpa
 * kolom ini, malam di tenda pada trip berkemah terhitung sebagai waktu bergerak. paused_at
 * terisi selama jeda berjalan; paused_seconds menjumlahkan jeda yang sudah selesai. Aditif:
 * baris lama mendapat 0 dan tanpa jeda berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('ended_at');
            $table->unsignedInteger('paused_seconds')->default(0)->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'paused_seconds']);
        });
    }
};
