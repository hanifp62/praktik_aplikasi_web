<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BMKG mengirim dua penanda waktu: `local_datetime` dalam waktu lokal Indonesia dan
 * `utc_datetime` yang tidak ambigu. Sebelumnya hanya `local_datetime` yang disimpan,
 * dan karena timezone aplikasi adalah UTC, nilai WIB itu tersimpan seolah-olah UTC:
 * seluruh jadwal prakiraan bergeser tujuh jam.
 *
 * `forecast_at` menyimpan instan sebenarnya dalam UTC dan menjadi satu-satunya kolom
 * yang dipakai untuk menyaring dan membandingkan. `local_datetime` dipertahankan
 * sebagai nilai tampilan asal BMKG.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->timestamp('forecast_at')->nullable()->after('local_datetime')->index();
        });

        // Baris lama hanya punya local_datetime yang tersimpan sebagai WIB. Backfill
        // mengembalikannya ke UTC. Gunung di zona WITA/WIT perlu diperiksa manual.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("UPDATE weather_snapshots SET forecast_at = local_datetime - interval '7 hours' WHERE forecast_at IS NULL");
        } elseif ($driver === 'sqlite') {
            DB::statement("UPDATE weather_snapshots SET forecast_at = datetime(local_datetime, '-7 hours') WHERE forecast_at IS NULL");
        }

        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->dropUnique(['adm4_code', 'local_datetime']);
            $table->unique(['adm4_code', 'forecast_at']);
        });
    }

    public function down(): void
    {
        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->dropUnique(['adm4_code', 'forecast_at']);
            $table->unique(['adm4_code', 'local_datetime']);
            $table->dropColumn('forecast_at');
        });
    }
};
