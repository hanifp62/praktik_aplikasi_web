<?php

use App\Enums\CheckpointType;
use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_segment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('name');
            $table->string('checkpoint_type')->default(CheckpointType::POS->value);
            $table->unsignedInteger('elevation_m')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['trail_id', 'sequence']);
        });

        PostGis::addGeography('checkpoints', 'location', 'POINT');
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');
    }
};
