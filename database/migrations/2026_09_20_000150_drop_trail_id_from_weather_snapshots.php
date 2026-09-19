<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot cuaca adalah milik satu area referensi BMKG (kode adm4), bukan milik satu
 * jalur. Beberapa jalur di kelurahan yang sama berbagi baris yang sama.
 *
 * Karena updateOrCreate berkunci pada (adm4_code, forecast_at), kolom trail_id berisi
 * jalur mana pun yang kebetulan diproses terakhir — nilainya menyesatkan. Lebih buruk,
 * cascadeOnDelete-nya membuat penghapusan satu jalur ikut menghapus data cuaca yang
 * masih dipakai jalur lain di area yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->dropForeign(['trail_id']);
            $table->dropColumn('trail_id');
        });
    }

    public function down(): void
    {
        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->foreignId('trail_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
