<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan ekstensi PostGIS aktif
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        Schema::create('mountains', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('elevation_mdpl'); // Descriptive attribute sesuai BR-02 PRD
            $table->string('region');
            $table->timestamps();
        });

        // Koordinat Lokasi Gunung (Point)
        DB::statement('ALTER TABLE mountains ADD COLUMN location geometry(Point, 4326);');
    }

    public function down(): void
    {
        Schema::dropIfExists('mountains');
    }
};