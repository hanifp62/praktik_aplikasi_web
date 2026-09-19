<?php

use App\Enums\OfficialStatusValue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_statuses', function (Blueprint $table) {
            $table->id();
            $table->morphs('statusable');
            $table->string('scope');
            $table->string('status')->default(OfficialStatusValue::UNKNOWN->value)->index();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['statusable_type', 'statusable_id', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_statuses');
    }
};
