<?php

namespace Tests\Feature;

use App\Listeners\RecordScheduledTaskRun;
use App\Models\ScheduledTaskRun;
use App\Services\SchedulerHealthService;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * Kesehatan tugas terjadwal.
 *
 * Kegagalan yang dijaga di sini bentuknya ketiadaan, bukan galat. Cron yang tidak pernah
 * dipasang tidak menghasilkan apa pun untuk dilihat: prakiraan cuaca berhenti diperbarui
 * dan status resmi yang kedaluwarsa berhenti diperiksa, keduanya diam-diam, dan setiap
 * halaman tetap tampak normal karena data lamanya masih ada di sana.
 */
class SchedulerHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function service(): SchedulerHealthService
    {
        return app(SchedulerHealthService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(string $kunci): array
    {
        foreach ($this->service()->report() as $baris) {
            if ($baris['kunci'] === $kunci) {
                return $baris;
            }
        }

        $this->fail("Tugas {$kunci} tidak ada di laporan.");
    }

    private function catat(string $kunci, string $status, Carbon $kapan): void
    {
        ScheduledTaskRun::create([
            'task' => $kunci,
            'status' => $status,
            'ran_at' => $kapan,
        ]);
    }

    /**
     * Keadaan awal sebuah pemasangan baru, dan justru yang paling sering luput: cron
     * belum pernah dipasang sama sekali.
     */
    public function test_a_task_that_never_ran_is_reported_as_such(): void
    {
        $this->assertSame(SchedulerHealthService::BELUM_PERNAH, $this->baris('weather:refresh')['keadaan']);
        $this->assertTrue($this->service()->needsAttention());
    }

    public function test_a_recent_successful_run_is_healthy(): void
    {
        $this->catat('weather:refresh', ScheduledTaskRun::SUCCESS, now()->subHours(3));
        $this->catat('data:freshness-check', ScheduledTaskRun::SUCCESS, now()->subHours(3));

        $this->assertSame(SchedulerHealthService::SEHAT, $this->baris('weather:refresh')['keadaan']);
        $this->assertFalse($this->service()->needsAttention());
    }

    /**
     * Bentuk kegagalan yang paling sering luput: tidak ada galat sama sekali, hanya
     * tidak ada yang berjalan lagi.
     */
    public function test_a_task_that_simply_stopped_running_is_overdue(): void
    {
        $this->catat('weather:refresh', ScheduledTaskRun::SUCCESS, now()->subHours(20));

        $this->assertSame(SchedulerHealthService::TERLAMBAT, $this->baris('weather:refresh')['keadaan']);
    }

    /**
     * Toleransinya lebih longgar dari jadwalnya. weather:refresh berjarak 12 jam, dan
     * ambang yang dipasang persis 12 jam akan menyala merah setiap beberapa hari tanpa
     * ada yang rusak.
     */
    public function test_a_slightly_late_run_is_not_yet_an_alarm(): void
    {
        $this->catat('weather:refresh', ScheduledTaskRun::SUCCESS, now()->subHours(13));

        $this->assertSame(SchedulerHealthService::SEHAT, $this->baris('weather:refresh')['keadaan']);
    }

    /**
     * Tugas yang berjalan tepat waktu lalu meledak bukan tugas yang sehat, meskipun
     * waktunya masih baru.
     */
    public function test_a_fresh_but_failed_run_is_not_healthy(): void
    {
        $this->catat('weather:refresh', ScheduledTaskRun::SUCCESS, now()->subHours(12));
        $this->catat('weather:refresh', ScheduledTaskRun::FAILED, now()->subMinutes(5));

        $baris = $this->baris('weather:refresh');

        $this->assertSame(SchedulerHealthService::GAGAL, $baris['keadaan']);
        $this->assertNotNull($baris['terakhir_berhasil'], 'Keberhasilan sebelumnya tetap disebut agar terlihat sejak kapan rusaknya.');
    }

    public function test_the_listener_records_a_finished_task(): void
    {
        $task = app(Schedule::class)->command('weather:refresh');

        app(RecordScheduledTaskRun::class)->handleFinished(new ScheduledTaskFinished($task, 1.25));

        $run = ScheduledTaskRun::firstOrFail();

        $this->assertSame('weather:refresh', $run->task);
        $this->assertSame(ScheduledTaskRun::SUCCESS, $run->status);
        $this->assertSame(1250, $run->runtime_ms);
    }

    /**
     * Perintah yang berjalan tetapi mengaku gagal lewat exit code. Tanpa pemeriksaan
     * itu, tugas yang gagal setiap hari tercatat sehat setiap hari.
     */
    public function test_a_nonzero_exit_code_is_recorded_as_a_failure(): void
    {
        $task = app(Schedule::class)->command('weather:refresh');
        $task->exitCode = 1;

        app(RecordScheduledTaskRun::class)->handleFinished(new ScheduledTaskFinished($task, 0.5));

        $this->assertSame(ScheduledTaskRun::FAILED, ScheduledTaskRun::firstOrFail()->status);
    }

    public function test_the_listener_records_a_failed_task_with_its_message(): void
    {
        $task = app(Schedule::class)->command('data:freshness-check');

        app(RecordScheduledTaskRun::class)->handleFailed(
            new ScheduledTaskFailed($task, new RuntimeException('BMKG tidak dapat dihubungi.'))
        );

        $run = ScheduledTaskRun::firstOrFail();

        $this->assertSame('data:freshness-check', $run->task);
        $this->assertSame(ScheduledTaskRun::FAILED, $run->status);
        $this->assertStringContainsString('BMKG', $run->summary);
    }

    /**
     * Penjaga yang paling berharga dari berkas ini.
     *
     * Tugas terjadwal yang ditambahkan besok tanpa masuk daftar pantau akan berjalan,
     * gagal, dan berhenti berjalan tanpa satu pun tanda, persis seperti keadaan sebelum
     * halaman ini ada. Test ini gagal saat itu terjadi, bukan berbulan kemudian.
     */
    public function test_every_scheduled_task_is_actually_monitored(): void
    {
        $tidakDipantau = [];

        foreach (app(Schedule::class)->events() as $event) {
            $perintah = (string) ($event->command ?? '');

            if ($perintah === '') {
                continue;
            }

            foreach (array_keys(SchedulerHealthService::TUGAS) as $kunci) {
                if (str_contains($perintah, $kunci)) {
                    continue 2;
                }
            }

            $tidakDipantau[] = $event->getSummaryForDisplay();
        }

        $this->assertSame(
            [],
            $tidakDipantau,
            'Tugas terjadwal berikut tidak terdaftar di SchedulerHealthService::TUGAS, '
                .'jadi berhentinya tidak akan terlihat: '.implode(', ', $tidakDipantau)
        );
    }
}
