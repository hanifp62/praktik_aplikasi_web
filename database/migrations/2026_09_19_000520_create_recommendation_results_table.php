<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->boolean('eligible')->default(true)->index();
            $table->string('label')->nullable()->index();
            $table->decimal('internal_score', 6, 2)->nullable();
            $table->json('matched_factors')->nullable();
            $table->json('failed_rules')->nullable();
            $table->json('warnings')->nullable();
            $table->json('explanation')->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_results');
    }
};
