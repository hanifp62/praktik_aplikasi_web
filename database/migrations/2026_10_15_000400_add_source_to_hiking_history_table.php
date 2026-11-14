<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pendakian dari dua sumber (FR-25, spec v3 bagian 9.3; rencana induk W7 butir 3):
 * TRIP untuk pendakian yang tercatat lewat siklus trip, MANUAL untuk pendakian di luar trip yang
 * dicatat pendaki sendiri. Catatan manual tidak punya trip, dan jalurnya boleh di luar katalog
 * (trail_name), jadi trip_plan_id dan trail_id kini boleh kosong. Baris lama seluruhnya dari
 * trip, jadi nilai bawaan TRIP benar untuk mereka.
 *
 * Migrasi aditif ini mengisi skema tanpa menyunting rintisan 2026_09_25_000919 (ruling W7-R2).
 *
 * Di PostgreSQL dua CHECK menjaga bentuk baris di lapisan basis data: baris TRIP selalu punya
 * trip dan baris MANUAL tidak pernah punya, dan setiap baris punya jalur katalog atau nama
 * jalur. SQLite (test) mengandalkan Form Request yang sama; perilaku PostgreSQL diuji
 * tests/Spatial/HikingHistorySourceSchemaTest (R-024).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_history', function (Blueprint $table) {
            $table->string('source', 10)->default('TRIP')->after('user_id');
            $table->string('trail_name', 120)->nullable()->after('trail_id');
        });

        Schema::table('hiking_history', function (Blueprint $table) {
            $table->unsignedBigInteger('trip_plan_id')->nullable()->change();
            $table->unsignedBigInteger('trail_id')->nullable()->change();
            // Progres membaca riwayat satu pendaki per tahun, urut tanggal.
            $table->index(['user_id', 'completed_at'], 'hiking_history_pengguna_tanggal');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE hiking_history ADD CONSTRAINT hiking_history_sumber_sah CHECK ((source = 'TRIP' AND trip_plan_id IS NOT NULL) OR (source = 'MANUAL' AND trip_plan_id IS NULL))");
            DB::statement('ALTER TABLE hiking_history ADD CONSTRAINT hiking_history_jalur_tercatat CHECK (trail_id IS NOT NULL OR trail_name IS NOT NULL)');
        }
    }

    /**
     * Catatan manual tidak punya trip, jadi tidak dapat hidup di skema lama: mengembalikan
     * migrasi ini menghapusnya lebih dulu.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE hiking_history DROP CONSTRAINT IF EXISTS hiking_history_sumber_sah');
            DB::statement('ALTER TABLE hiking_history DROP CONSTRAINT IF EXISTS hiking_history_jalur_tercatat');
        }

        DB::table('hiking_history')->where('source', 'MANUAL')->delete();

        Schema::table('hiking_history', function (Blueprint $table) {
            $table->dropIndex('hiking_history_pengguna_tanggal');
            $table->unsignedBigInteger('trip_plan_id')->nullable(false)->change();
            $table->unsignedBigInteger('trail_id')->nullable(false)->change();
        });

        Schema::table('hiking_history', function (Blueprint $table) {
            $table->dropColumn(['source', 'trail_name']);
        });
    }
};
