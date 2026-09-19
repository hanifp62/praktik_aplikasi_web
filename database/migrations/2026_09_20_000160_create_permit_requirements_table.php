<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan perizinan pendakian (SIMAKSI).
 *
 * Realita pendakian Indonesia 2026: taman nasional menerapkan kuota harian dan booking
 * daring dengan batas waktu. Semeru misalnya membatasi 200 pendaki per hari lewat
 * sistem TNBTS, menutup pemesanan H-2, dan mewajibkan pemandu terdaftar. Tanpa data
 * ini, sistem akan menyatakan trip besok siap padahal izinnya sudah mustahil didapat.
 *
 * Aturan melekat pada jalur bila spesifik, atau pada gunung bila berlaku umum.
 * Mengikuti model kepercayaan PRD §60, tiap baris membawa sumber dan waktu verifikasi —
 * aturan perizinan berubah dan data yang tidak bertanggal tidak dapat dipercaya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('mountain_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('authority');
            $table->string('booking_url')->nullable();
            $table->unsignedInteger('daily_quota')->nullable();

            // Jendela pemesanan dihitung dalam hari sebelum tanggal pendakian.
            $table->unsignedInteger('booking_opens_days_before')->nullable();
            $table->unsignedInteger('booking_closes_days_before')->nullable();

            $table->boolean('guide_required')->default(false);
            $table->unsignedInteger('max_duration_days')->nullable();
            $table->text('notes')->nullable();

            // PRD §60: sumber, waktu, dan status verifikasi menyertai setiap data penting.
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index('trail_id');
            $table->index('mountain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_requirements');
    }
};
