<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jam kembali dan nomor registrasi untuk pesan rencana ke kontak (spec v3 bagian 10.8 dan
 * bagian 15 Q14: "kolom nomor registrasi dan jam kembali"; ruling W6-R4).
 *
 * Jam kembali disimpan sebagai waktu UTC dan ditampilkan dalam zona gunung. Keduanya diisi
 * pendaki sendiri dan boleh kosong; kosong berarti belum disebut, bukan "tanpa batas" (R-007).
 * Aditif dan boleh kosong untuk baris lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->timestamp('expected_return_at')->nullable()->after('end_date');
            $table->string('registration_number', 60)->nullable()->after('expected_return_at');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropColumn(['expected_return_at', 'registration_number']);
        });
    }
};
