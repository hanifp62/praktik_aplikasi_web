<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci stabil butir persiapan sistem (titipan W8, ruling W4-C7b; ruling W8-R24). Butir izin
 * pendakian dikenali lewat kolom jenis, bukan lewat teks labelnya, supaya labelnya dapat berganti
 * tanpa memutus Persiapan, Cek kesiapan, Beranda, dan pengingat yang membacanya. Kosong untuk
 * butir templat dan butir perlengkapan pribadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->string('kind', 20)->nullable()->after('user_owned_item_id');
            $table->index(['trip_plan_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->dropIndex(['trip_plan_id', 'kind']);
            $table->dropColumn('kind');
        });
    }
};
