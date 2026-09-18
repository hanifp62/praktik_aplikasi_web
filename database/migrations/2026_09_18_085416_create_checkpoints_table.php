<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Contoh: "Pos 1 - Kandang Badak"
            $table->integer('order_index'); // Urutan pos
            $table->timestamps();
        });

        // Titik Checkpoint (Point)
        DB::statement('ALTER TABLE checkpoints ADD COLUMN location geometry(Point, 4326);');
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');
    }
};