<?php

use App\Enums\TechnicalDemand;
use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trail_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedInteger('elevation_gain_m')->nullable();
            $table->string('technical_demand')->default(TechnicalDemand::MODERATE->value);
            $table->json('terrain_character')->nullable();
            $table->timestamps();

            $table->unique(['trail_id', 'sequence']);
        });

        PostGis::addGeography('trail_segments', 'geometry', 'LINESTRING');
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_segments');
    }
};
