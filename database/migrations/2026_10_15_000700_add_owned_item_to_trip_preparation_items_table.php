<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautan butir persiapan ke perlengkapan pribadi yang mengisinya (ruling W7-R8): supaya
 * "Perbarui daftar" tidak menggandakan barang yang sama, dan supaya butirnya dapat menyebut
 * asalnya. Menghapus barang dari Profil tidak menghapus butir trip yang sudah ada (nullOnDelete):
 * daftar persiapan trip adalah catatan trip itu. Aditif dan boleh kosong untuk butir lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->foreignId('user_owned_item_id')->nullable()->after('preparation_item_id')->constrained('user_owned_items')->nullOnDelete();
            $table->index('user_owned_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->dropIndex(['user_owned_item_id']);
            $table->dropConstrainedForeignId('user_owned_item_id');
        });
    }
};
