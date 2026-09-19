<?php

use App\Enums\HikingSessionStatus;
use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_checkpoint_id')->nullable()->constrained('checkpoints')->nullOnDelete();
            $table->string('status')->default(HikingSessionStatus::ACTIVE->value)->index();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamps();
        });

        PostGis::addGeography('hiking_sessions', 'last_known_location', 'POINT');
    }

    public function down(): void
    {
        Schema::dropIfExists('hiking_sessions');
    }
};
