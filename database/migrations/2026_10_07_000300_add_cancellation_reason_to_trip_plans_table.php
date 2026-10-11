<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alasan pembatalan trip (DK-50, spec v3 bagian 9.3): kolom opsional dengan daftar putih
 * App\Enums\CancellationReason yang ditegakkan validasi, bukan basis data. Aditif dan boleh
 * kosong: trip lama tanpa alasan tetap sah, dan kosong berarti tidak disebut, bukan "lainnya"
 * (R-007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->string('cancellation_reason', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
