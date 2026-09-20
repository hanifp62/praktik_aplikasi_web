<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gunung yang diikuti seorang pendaki.
 *
 * Satu-satunya Trigger dalam kerangka Fogg di aplikasi ini. Seluruh layar lain melayani
 * seseorang yang sudah memutuskan merencanakan pendakian; tidak ada satu pun yang
 * memberi alasan membukanya di antara dua pendakian, dan tanpa alasan itu aplikasi
 * perencanaan dipakai sekali per pendakian lalu dilupakan.
 *
 * Diikuti gunungnya, bukan jalurnya. Penutupan hampir selalu diumumkan untuk kawasan,
 * dan pendaki yang mengikuti satu jalur Merbabu tetap perlu tahu ketika seluruh
 * Merbabu ditutup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mountain_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'mountain_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mountain_follows');
    }
};
