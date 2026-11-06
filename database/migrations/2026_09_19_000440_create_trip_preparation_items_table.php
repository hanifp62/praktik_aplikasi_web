<?php

use App\Enums\PreparationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_preparation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('preparation_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->string('status')->default(PreparationStatus::NOT_CONFIRMED->value);
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_preparation_items');
    }
};
