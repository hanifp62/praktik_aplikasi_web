<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTaskRun;
use App\Services\Admin\DataHealthSummary;
use App\Services\Admin\MigrationStatus;
use App\Services\SchedulerHealthService;
use App\Support\Timezone;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kesehatan tugas terjadwal (PRD §98). Baca-saja.
 *
 * Halaman ini ada untuk satu bentuk kegagalan yang tidak pernah menghasilkan galat:
 * scheduler yang tidak berjalan. Data lama tetap di tempatnya, setiap halaman tetap
 * tampak normal, dan satu-satunya tanda adalah sesuatu yang tidak terjadi.
 *
 * Ringkasan kesehatan data jalur ikut di sini (features/08, "Ringkasan Kesehatan
 * Data"): kekurangan yang sama senyapnya, dan pengelola perlu melihat keduanya di
 * satu tempat sebelum menambah data baru.
 */
class SystemHealthController extends Controller
{
    public function __invoke(SchedulerHealthService $kesehatan, DataHealthSummary $data, MigrationStatus $migrasi): Response
    {
        $tugas = $kesehatan->report();

        return Inertia::render('Admin/Health', [
            'title' => 'Kesehatan Sistem',
            // Yang perlu dibenahi pada data jalur, dari gerbang yang sama dengan penerbitan.
            'kesehatanData' => $data->ringkasan(),
            // Migrasi yang belum jalan di basis data ini membuat halaman bertabel baru galat.
            'migrasi' => $migrasi->laporan(),
            'perluDiurus' => collect($tugas)->contains(fn (array $b) => $b['keadaan'] !== SchedulerHealthService::SEHAT),
            'tugas' => array_map(fn (array $b) => [
                'kunci' => $b['kunci'],
                'judul' => $b['judul'],
                'jadwal' => $b['jadwal'],
                'toleransiJam' => $b['toleransi_jam'],
                'terakhirJalan' => $b['terakhir_jalan'] ? Timezone::display($b['terakhir_jalan'], Timezone::DEFAULT) : 'belum pernah',
                'terakhirBerhasil' => $b['terakhir_berhasil'] ? Timezone::display($b['terakhir_berhasil'], Timezone::DEFAULT) : 'belum pernah',
                'ringkasan' => $b['ringkasan'],
                'keadaan' => $b['keadaan'],
                'keadaanLabel' => $kesehatan->label($b['keadaan']),
            ], $tugas),
            'riwayat' => ScheduledTaskRun::query()->latest('ran_at')->limit(20)->get()
                ->map(fn (ScheduledTaskRun $run) => [
                    'id' => $run->id,
                    'task' => $run->task,
                    'berhasil' => $run->isSuccess(),
                    'durasi' => $run->runtime_ms !== null ? number_format($run->runtime_ms / 1000, 2, ',', '.').' dtk' : null,
                    'waktu' => Timezone::display($run->ran_at, Timezone::DEFAULT),
                ])
                ->values()
                ->all(),
        ]);
    }
}
