<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('adm4_code')->index();
            $table->string('reference_area')->nullable();
            $table->timestamp('local_datetime')->index();
            $table->string('weather_description')->nullable();
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->unsignedInteger('humidity_percent')->nullable();
            $table->decimal('wind_speed_kmh', 6, 2)->nullable();
            $table->string('wind_direction')->nullable();
            $table->unsignedInteger('cloud_cover_percent')->nullable();
            $table->unsignedInteger('visibility_m')->nullable();
            $table->timestamp('analysis_date')->nullable();
            $table->string('source')->default('BMKG');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['adm4_code', 'local_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_snapshots');
    }
};
