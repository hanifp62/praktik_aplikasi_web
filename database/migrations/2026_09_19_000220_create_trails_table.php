<?php

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use App\Enums\WaterAvailability;
use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedInteger('elevation_gain_m')->nullable();
            $table->unsignedInteger('elevation_loss_m')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->string('technical_demand')->default(TechnicalDemand::MODERATE->value);
            $table->json('terrain_character')->nullable();
            $table->string('navigation_complexity')->default(NavigationComplexity::MODERATE->value);
            $table->string('water_availability')->default(WaterAvailability::UNKNOWN->value);
            $table->boolean('camping_available')->default(false);
            $table->string('starting_point')->nullable();
            $table->string('weather_adm4_code')->nullable()->index();
            $table->string('weather_reference_area')->nullable();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        PostGis::addGeography('trails', 'geometry', 'LINESTRING');
    }

    public function down(): void
    {
        Schema::dropIfExists('trails');
    }
};
