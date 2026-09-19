<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indonesia punya tiga zona waktu. Menyimpan seluruh waktu dalam UTC hanya benar bila
 * ada yang menentukan zona mana yang dipakai saat menampilkannya kembali; gunung adalah
 * tempat yang tepat karena zona melekat pada lokasinya, bukan pada penggunanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mountains', function (Blueprint $table) {
            $table->string('timezone')->default('Asia/Jakarta')->after('region');
        });
    }

    public function down(): void
    {
        Schema::table('mountains', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
