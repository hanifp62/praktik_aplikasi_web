<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak setiap kali tugas terjadwal benar-benar berjalan.
 *
 * Sebelum tabel ini, satu-satunya bukti adalah baris log. Log memberi tahu bahwa sebuah
 * run terjadi; ia tidak pernah memberi tahu bahwa run tidak terjadi. Cron yang mati
 * karena itu tidak menghasilkan apa pun untuk dilihat, dan justru ketiadaan itulah yang
 * perlu terlihat: prakiraan cuaca berhenti diperbarui dan status resmi yang kedaluwarsa
 * berhenti diperiksa, keduanya tanpa satu pun tanda di layar mana pun.
 *
 * Tanpa pemangkasan, dan itu disengaja. Tiga baris sehari berarti sekitar seribu seratus
 * baris setahun. Memasang mesin prune untuknya menambah satu tugas terjadwal yang ikut
 * perlu dipantau, demi tabel yang tumbuh lebih lambat daripada hampir semua tabel lain
 * di sini. Retensi memang perlu diputuskan menyeluruh bersama audit_logs dan
 * analytics_events yang jauh lebih besar, bukan dimulai dari yang terkecil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_runs', function (Blueprint $table) {
            $table->id();
            $table->string('task')->index();
            $table->string('status', 20);
            $table->unsignedInteger('runtime_ms')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('ran_at');
            $table->timestamps();

            // Pertanyaan yang selalu ditanyakan halaman kesehatan adalah "kapan tugas
            // ini terakhir berhasil", jadi indeksnya mengikuti bentuk pertanyaan itu.
            $table->index(['task', 'status', 'ran_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_runs');
    }
};
