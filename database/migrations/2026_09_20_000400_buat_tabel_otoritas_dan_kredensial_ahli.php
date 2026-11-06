<?php

use App\Enums\VerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Badan resmi dan kredensial ahli yang mereka sahkan (PRD §43, §60).
 *
 * Sebelum ini, pihak resmi hanya berupa teks bebas di data_sources.source_owner, dan
 * hanya admin yang boleh menyentuh data jalur. Akibatnya pemandu bersertifikat yang
 * paling tahu keadaan lapangan tidak punya pintu masuk sama sekali.
 *
 * Skema sertifikasinya nyata: BNSP menetapkan standarnya, LSP menguji, APGI menaungi,
 * dan jenjangnya Muda, Madya, Ahli menurut SKKNI. Sertifikatnya berlaku tiga tahun.
 *
 * Masa berlaku itu yang membuat rancangan ini aman: hak menyumbang data gugur sendiri
 * ketika sertifikatnya kedaluwarsa, tanpa perlu ada yang ingat mencabutnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->index();
            $table->string('abbreviation')->nullable();
            $table->string('website')->nullable();
            $table->string('contact')->nullable();

            // Wilayah kewenangannya, misalnya "Jawa Tengah" atau "nasional".
            $table->string('jurisdiction')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // Satu badan dapat berwenang atas banyak gunung, dan satu gunung dapat berada di
        // bawah lebih dari satu badan: balai taman nasional untuk kawasannya, pengelola
        // basecamp untuk jalurnya.
        Schema::create('authority_mountain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['authority_id', 'mountain_id']);
        });

        Schema::create('expert_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Yang menerbitkan sertifikatnya, misalnya LSP atau BNSP lewat APGI.
            $table->foreignId('issuing_authority_id')->constrained('authorities')->cascadeOnDelete();

            // Yang menyetujui orang ini menyumbang data di kawasannya, biasanya balai
            // taman nasional. Boleh kosong untuk sertifikat yang belum diendorse.
            $table->foreignId('endorsing_authority_id')->nullable()->constrained('authorities')->nullOnDelete();

            $table->string('scheme')->default('Pemandu Wisata Gunung');
            $table->string('level')->index();
            $table->string('certificate_number')->nullable();

            $table->date('issued_at');
            $table->date('expires_at')->nullable();

            // Kami yang memverifikasi bahwa sertifikatnya benar ada, bukan menerbitkannya.
            $table->string('verification_status')->default(VerificationStatus::UNVERIFIED->value)->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verification_status']);
        });

        // Kredensial mengikat orang ke gunung tertentu. Seorang ahli yang menguasai
        // Merbabu tidak otomatis berwenang atas Rinjani.
        Schema::create('credential_mountain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['expert_credential_id', 'mountain_id']);
        });

        Schema::table('trails', function (Blueprint $table) {
            // Siapa yang menyumbang data jalur ini. Nol berarti belum ada yang mengisi,
            // dan itu keadaan yang sah, bukan kerusakan.
            $table->foreignId('contributed_by')->nullable()->after('data_source_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('contributed_credential_id')->nullable()->after('contributed_by')
                ->constrained('expert_credentials')->nullOnDelete();
            $table->timestamp('contributed_at')->nullable()->after('contributed_credential_id');
        });
    }

    public function down(): void
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contributed_credential_id');
            $table->dropConstrainedForeignId('contributed_by');
            $table->dropColumn('contributed_at');
        });

        Schema::dropIfExists('credential_mountain');
        Schema::dropIfExists('expert_credentials');
        Schema::dropIfExists('authority_mountain');
        Schema::dropIfExists('authorities');
    }
};
