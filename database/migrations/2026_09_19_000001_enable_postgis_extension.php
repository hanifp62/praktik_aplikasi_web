<?php

use App\Support\PostGis;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PostGis::enableExtension();
    }

    public function down(): void
    {
        // The extension is intentionally left in place: other schemas in the same Supabase
        // project may depend on it.
    }
};
