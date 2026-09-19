<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recommendation_result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('computed_state')->index();
            $table->json('route_fit_snapshot')->nullable();
            $table->json('preparation_state')->nullable();
            $table->json('official_status_snapshot')->nullable();
            $table->json('condition_snapshot')->nullable();
            $table->json('explanation')->nullable();
            $table->boolean('pre_departure_confirmed')->default(false);
            $table->timestamp('computed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readiness_checks');
    }
};
