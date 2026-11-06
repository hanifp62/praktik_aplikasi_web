<?php

use App\Enums\VerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->string('source_type')->index();
            $table->string('source_url')->nullable();
            $table->string('source_owner')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('freshness_policy')->nullable();
            $table->string('verification_status')->default(VerificationStatus::UNVERIFIED->value);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
