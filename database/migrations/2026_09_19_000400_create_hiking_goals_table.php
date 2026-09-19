<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiking_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('target_date')->nullable();
            $table->string('region')->nullable();
            $table->string('trip_type');
            $table->unsignedInteger('expected_duration_minutes')->nullable();
            $table->string('preferred_challenge')->nullable();
            $table->unsignedInteger('max_elevation_gain_m')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiking_goals');
    }
};
