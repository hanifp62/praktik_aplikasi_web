<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan moderasi adalah jejak tata kelola dan alasan penolakan yang masih dibaca pelapor
 * (`TrailConditionReport::rejectionReason()`). Ketika akun moderator dihapus, baris tindakan
 * moderasi tetap dipertahankan tanpa kaitan identitas (`moderator_id = null`), selaras dengan
 * `docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 2, 3, dan 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moderation_actions', function (Blueprint $table) {
            $table->dropForeign(['moderator_id']);
            $table->unsignedBigInteger('moderator_id')->nullable()->change();
            $table->foreign('moderator_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('moderation_actions')->whereNull('moderator_id')->delete();

        Schema::table('moderation_actions', function (Blueprint $table) {
            $table->dropForeign(['moderator_id']);
            $table->unsignedBigInteger('moderator_id')->nullable(false)->change();
            $table->foreign('moderator_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
