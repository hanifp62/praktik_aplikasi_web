<?php

use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restricted_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('reason')->nullable();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        PostGis::addGeography('restricted_areas', 'geometry', 'POLYGON');
    }

    public function down(): void
    {
        Schema::dropIfExists('restricted_areas');
    }
};
