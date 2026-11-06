<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan kondisi memakai cascadeOnDelete pada user_id, sehingga menghapus akun ikut
 * menghancurkan laporan yang sudah disetujui dan menjadi rujukan pendaki lain.
 *
 * PRD §84 mengecualikan konten yang dibutuhkan untuk integritas, dan §104 menuntut
 * kebijakan retensi. Melepas kaitan penulisnya menghapus identitas pribadinya tanpa
 * merusak intel lapangan yang orang lain andalkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trail_condition_reports', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trail_condition_reports', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
