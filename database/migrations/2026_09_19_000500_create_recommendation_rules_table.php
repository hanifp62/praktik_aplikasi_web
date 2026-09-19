<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 4)->default(0);
            $table->boolean('active')->default(true)->index();
            $table->string('engine_version')->default('v1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_rules');
    }
};
