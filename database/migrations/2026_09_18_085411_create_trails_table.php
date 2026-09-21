<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Contoh: "Jalur Cibodas"
            $table->decimal('distance_km', 5, 2);
            $table->integer('elevation_gain_m');
            $table->integer('estimated_duration_hours');
            $table->string('technical_demand'); // EASY, MODERATE, HARD
            $table->string('terrain_character');
            $table->timestamps();
        });

        // Jalur Rute Pendakian (LineString)
        DB::statement('ALTER TABLE trails ADD COLUMN route_geometry geometry(LineString, 4326);');
    }

    public function down(): void
    {
        Schema::dropIfExists('trails');
    }
};