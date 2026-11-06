<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Izin merekam jejak pendakian, default mati.
 *
 * Default mati bukan kehati-hatian berlebihan. Riwayat lokasi memberi tahu di mana
 * seseorang berada pada jam berapa selama berhari-hari, dan itu data paling pribadi yang
 * disimpan aplikasi ini. Fitur yang menyala sendiri berarti pendaki menyerahkannya tanpa
 * pernah memutuskan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->boolean('record_track')->default(false)->after('region_preference');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('record_track');
        });
    }
};
