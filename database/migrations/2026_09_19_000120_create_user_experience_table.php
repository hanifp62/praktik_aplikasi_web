<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_experience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('completed_hikes_count')->default(0);
            $table->json('terrain_experience')->nullable();
            $table->string('navigation_experience')->nullable();
            $table->unsignedInteger('longest_hike_duration_minutes')->nullable();
            $table->unsignedInteger('highest_elevation_gain_m')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_experience');
    }
};
