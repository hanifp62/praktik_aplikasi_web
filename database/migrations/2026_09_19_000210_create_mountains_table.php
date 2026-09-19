<?php

use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mountains', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('province')->nullable();
            $table->string('region')->nullable()->index();
            $table->unsignedInteger('elevation_mdpl')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        PostGis::addGeography('mountains', 'location', 'POINT');
    }

    public function down(): void
    {
        Schema::dropIfExists('mountains');
    }
};
