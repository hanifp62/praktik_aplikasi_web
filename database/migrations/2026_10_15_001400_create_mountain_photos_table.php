<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 8.7 dan 9.3; rencana induk W8 butir 8; ruling
 * W8-R19): satu foto per gunung, diunggah admin, dengan kolom wajib pembuat, lisensi, URL sumber
 * (kecuali milik sendiri), teks atribusi, dan teks alternatif. Berkasnya ditulis ulang tanpa EXIF
 * dan sidik SHA-256-nya dicatat. Foto komunitas tidak pernah masuk tabel ini (R-017).
 *
 * Foto ikut terhapus bersama gunungnya; pengunggah menjadi kosong bila akunnya hilang. Di
 * PostgreSQL CHECK menjaga lisensi dari daftar tertutup dan sidiknya, dan RLS menutup REST API
 * (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mountain_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->char('sha256', 64);
            $table->string('creator', 160);
            $table->string('license', 20);
            $table->string('source_url', 500)->nullable();
            $table->string('attribution', 300);
            $table->string('alt_text', 300);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('uploaded_by');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE mountain_photos ADD CONSTRAINT mountain_photos_lisensi_sah CHECK (license IN ('CC_BY', 'CC_BY_SA', 'PUBLIC_DOMAIN', 'OWN'))");
            DB::statement("ALTER TABLE mountain_photos ADD CONSTRAINT mountain_photos_sha256_sah CHECK (sha256 ~ '^[0-9a-f]{64}$')");
            DB::statement('ALTER TABLE public."mountain_photos" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mountain_photos');
    }
};
