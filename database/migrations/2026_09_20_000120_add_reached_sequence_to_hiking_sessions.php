<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hike Mode sebelumnya menentukan checkpoint berikutnya sebagai "satu setelah yang
 * terdekat". Pendaki yang masih 100 m sebelum Pos 3 paling dekat ke Pos 3, sehingga
 * sistem menunjuk Pos 4 — pos yang sedang dituju justru dilewati dan jarak yang
 * ditampilkan mengarah ke pos yang salah.
 *
 * Kemajuan perlu diingat: sebuah pos dianggap tercapai setelah pendaki pernah berada
 * dalam radius kedatangannya, bukan disimpulkan ulang dari posisi saat ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->unsignedInteger('reached_checkpoint_sequence')->nullable()->after('current_checkpoint_id');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->dropColumn('reached_checkpoint_sequence');
        });
    }
};
