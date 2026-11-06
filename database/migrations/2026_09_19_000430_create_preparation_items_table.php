<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preparation_template_id')->constrained()->cascadeOnDelete();
            $table->string('category')->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('applies_when')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preparation_items');
    }
};
