<?php

namespace App\Listeners;

use App\Models\ScheduledTaskRun;
use App\Services\SchedulerHealthService;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;

/**
 * Mencatat setiap kali tugas terjadwal selesai, berhasil maupun tidak.
 *
 * Dipasang pada event scheduler, bukan di dalam perintahnya masing-masing. Perintah yang
 * mencatat dirinya sendiri hanya mencatat perintah yang ingat melakukannya, dan tugas
 * yang ditambahkan besok akan luput tanpa siapa pun menyadarinya.
 */
class RecordScheduledTaskRun
{
    public function handleFinished(ScheduledTaskFinished $event): void
    {
        // exitCode bukan nol berarti perintahnya berjalan tetapi mengaku gagal. Tanpa
        // pemeriksaan ini, tugas yang gagal setiap hari akan tercatat sehat setiap hari.
        $this->record(
            $event->task,
            ($event->task->exitCode ?? 0) === 0 ? ScheduledTaskRun::SUCCESS : ScheduledTaskRun::FAILED,
            (int) round($event->runtime * 1000),
            ($event->task->exitCode ?? 0) === 0 ? null : 'Keluar dengan kode '.$event->task->exitCode.'.'
        );
    }

    public function handleFailed(ScheduledTaskFailed $event): void
    {
        $this->record(
            $event->task,
            ScheduledTaskRun::FAILED,
            null,
            mb_substr($event->exception->getMessage(), 0, 500)
        );
    }

    private function record(ScheduledEvent $task, string $status, ?int $runtimeMs, ?string $summary): void
    {
        $kunci = $this->taskKey($task);

        if ($kunci === null) {
            return;
        }

        ScheduledTaskRun::create([
            'task' => $kunci,
            'status' => $status,
            'runtime_ms' => $runtimeMs,
            'summary' => $summary,
            'ran_at' => now(),
        ]);
    }

    /**
     * Mencocokkan perintah yang dijalankan dengan kunci tugas yang dipantau.
     *
     * Scheduler menyimpan perintahnya sebagai baris shell lengkap dengan jalur PHP dan
     * artisan, dan bentuk baris itu berbeda antar sistem operasi. Yang tetap sama di
     * mana pun adalah nama perintah artisan-nya, jadi itu yang dicari.
     *
     * Tugas yang tidak dikenal sengaja tidak dicatat. Halaman kesehatan hanya melaporkan
     * tugas yang punya harapan jadwal, dan baris tanpa harapan tidak dapat dinilai
     * terlambat atau tidak. Test terpisah menjaga agar tugas terjadwal baru tidak
     * tertinggal di luar daftar itu.
     */
    private function taskKey(ScheduledEvent $task): ?string
    {
        $perintah = (string) ($task->command ?? '');

        foreach (array_keys(SchedulerHealthService::TUGAS) as $kunci) {
            if (str_contains($perintah, $kunci)) {
                return $kunci;
            }
        }

        return null;
    }
}
