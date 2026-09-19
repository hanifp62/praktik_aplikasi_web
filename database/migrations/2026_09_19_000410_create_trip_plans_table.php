<?php

use App\Enums\TripStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hiking_goal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('planned_date');
            $table->time('start_time')->nullable();
            $table->string('trip_type');
            $table->text('notes')->nullable();
            $table->string('status')->default(TripStatus::DRAFT->value)->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_plans');
    }
};
