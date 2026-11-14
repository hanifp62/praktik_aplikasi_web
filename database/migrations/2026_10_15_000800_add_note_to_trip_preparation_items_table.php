<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan singkat per butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling
 * W7-R9). Aditif dan boleh kosong: butir lama tetap tanpa catatan. Panjangnya dibatasi kolom,
 * bukan hanya validasi, supaya batas yang sama berlaku di PostgreSQL. Catatan ikut terhapus
 * bersama butir, trip, dan akun lewat cascade yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->string('note', 80)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
