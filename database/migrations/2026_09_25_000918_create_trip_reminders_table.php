<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengingat perjalanan H-3 dan H-1 (FR-24).
 *
 * Satu baris per trip per jenis. Kunci unik itu, ditambah klaim atomik pada sent_at
 * di App\Services\Trips\TripReminderScheduler, yang menjamin satu pengingat tidak
 * terkirim dua kali meskipun perintah terjadwal berjalan tumpang tindih.
 *
 * dijadwalkan_untuk adalah instan UTC dari pukul 07:00 zona gunungnya. skipped_at
 * mencatat pengingat yang sengaja tidak dikirim (hari keberangkatan sudah tiba, atau
 * tersusul pengingat yang lebih dekat), supaya halaman trip tidak menampilkannya
 * sebagai "menunggu" selamanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_plan_id')->constrained()->cascadeOnDelete();
            $table->string('jenis', 8);
            $table->timestamp('dijadwalkan_untuk')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->unique(['trip_plan_id', 'jenis'], 'trip_reminders_unik');
        });

        // Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak
        // REST API, sedangkan Laravel sebagai pemilik tabel tidak terpengaruh.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."trip_reminders" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_reminders');
    }
};
