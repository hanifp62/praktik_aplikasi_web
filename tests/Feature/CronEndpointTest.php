<?php

namespace Tests\Feature;

use App\Http\Controllers\CronController;
use App\Models\ScheduledTaskRun;
use App\Services\SchedulerHealthService;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

/**
 * Rute pemicu Vercel Cron (DK-12, PRD §154 K24).
 *
 * Rute ini menjalankan perintah artisan atas permintaan dari internet, jadi yang dijaga lebih dulu adalah
 * penolakannya: tanpa rahasia yang tepat tidak ada yang berjalan, dan dengan rahasia yang tepat hanya tugas
 * yang dipantau yang dapat dipicu. Sesudah itu, setiap jalannya tercatat untuk halaman kesehatan sama seperti
 * jalan dari scheduler, termasuk yang gagal.
 */
class CronEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const RAHASIA = 'rahasia-cron-uji-yang-cukup-panjang';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.cron.secret' => self::RAHASIA]);
    }

    public function test_without_a_configured_secret_every_call_is_refused(): void
    {
        config(['services.cron.secret' => null]);

        $this->getJson(route('api.cron', 'data-freshness-check'))->assertUnauthorized();
        $this->withToken('')->getJson(route('api.cron', 'data-freshness-check'))->assertUnauthorized();

        $this->assertSame(0, ScheduledTaskRun::count());
    }

    public function test_a_call_without_the_exact_bearer_secret_is_refused(): void
    {
        $this->getJson(route('api.cron', 'data-freshness-check'))->assertUnauthorized();
        $this->withToken('tebakan')->getJson(route('api.cron', 'data-freshness-check'))->assertUnauthorized();
        $this->withHeader('Authorization', self::RAHASIA)->getJson(route('api.cron', 'data-freshness-check'))->assertUnauthorized();

        $this->assertSame(0, ScheduledTaskRun::count());
    }

    /**
     * Rahasia diperiksa lebih dulu: pemanggil tanpa rahasia mendapat jawaban yang sama untuk tugas yang ada
     * maupun tidak, sehingga tidak dapat memetakan tugas mana yang dapat dipicu.
     */
    public function test_the_secret_is_checked_before_the_task_is_looked_up(): void
    {
        $this->withToken('tebakan')->getJson(route('api.cron', 'tidak-ada'))->assertUnauthorized();
    }

    /**
     * Daftar putihnya SchedulerHealthService::TUGAS. Perintah artisan lain, termasuk yang merusak data,
     * tidak dapat dipicu meski rahasianya benar.
     */
    public function test_only_monitored_tasks_can_be_triggered(): void
    {
        foreach (['tidak-ada', 'inspire', 'migrate', 'migrate-fresh', 'db-wipe', 'memount-demo'] as $slug) {
            $this->withToken(self::RAHASIA)->getJson(route('api.cron', $slug))->assertNotFound();
        }

        $this->assertSame(0, ScheduledTaskRun::count());
    }

    public function test_a_monitored_task_runs_and_is_recorded_for_the_health_page(): void
    {
        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'data-freshness-check'))
            ->assertOk()
            ->assertJson(['tugas' => 'data:freshness-check', 'keadaan' => 'berhasil', 'kode' => 0])
            ->assertCookieMissing(config('session.cookie'));

        $jalan = ScheduledTaskRun::sole();
        $this->assertSame('data:freshness-check', $jalan->task);
        $this->assertSame(ScheduledTaskRun::SUCCESS, $jalan->status);
        $this->assertNotNull($jalan->runtime_ms);

        $baris = collect(app(SchedulerHealthService::class)->report())->firstWhere('kunci', 'data:freshness-check');
        $this->assertSame(SchedulerHealthService::SEHAT, $baris['keadaan']);
    }

    /**
     * Perintah yang selesai tetapi mengaku gagal tercatat gagal, dan Vercel melihat kode 500 di lognya.
     */
    public function test_a_command_that_exits_with_failure_is_recorded_as_failed(): void
    {
        $this->mock(TripReminderScheduler::class)
            ->shouldReceive('kirimYangJatuhTempo')
            ->andReturn(['terkirim' => 0, 'dilewati' => 0, 'gagal' => 1]);

        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'trips-kirim-pengingat'))
            ->assertStatus(500)
            ->assertJson(['tugas' => 'trips:kirim-pengingat', 'keadaan' => 'gagal', 'kode' => 1]);

        $jalan = ScheduledTaskRun::sole();
        $this->assertSame(ScheduledTaskRun::FAILED, $jalan->status);
        $this->assertSame('Keluar dengan kode 1.', $jalan->summary);
    }

    public function test_a_command_that_throws_is_recorded_with_its_message(): void
    {
        $this->mock(TripReminderScheduler::class)
            ->shouldReceive('kirimYangJatuhTempo')
            ->andThrow(new RuntimeException('Server surel tidak dapat dihubungi.'));

        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'trips-kirim-pengingat'))
            ->assertStatus(500)
            ->assertJson(['tugas' => 'trips:kirim-pengingat', 'keadaan' => 'gagal']);

        $jalan = ScheduledTaskRun::sole();
        $this->assertSame(ScheduledTaskRun::FAILED, $jalan->status);
        $this->assertStringContainsString('Server surel', $jalan->summary);
    }

    /**
     * Galat di tengah jalan tetap melepas kuncinya, jadi jadwal berikutnya berjalan lagi, bukan terus "dilewati".
     */
    public function test_a_run_that_throws_still_releases_its_lock(): void
    {
        $this->mock(TripReminderScheduler::class)
            ->shouldReceive('kirimYangJatuhTempo')
            ->andThrow(new RuntimeException('Server surel tidak dapat dihubungi.'));

        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'trips-kirim-pengingat'))->assertStatus(500);
        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'trips-kirim-pengingat'))
            ->assertStatus(500)
            ->assertJson(['keadaan' => 'gagal']);

        $this->assertSame(2, ScheduledTaskRun::count());
    }

    /**
     * Vercel kadang memicu satu jadwal dua kali. Panggilan kedua selagi yang pertama berjalan dilewati: tidak
     * menjalankan perintahnya lagi dan tidak menambah catatan.
     */
    public function test_a_run_already_in_progress_is_skipped_not_doubled(): void
    {
        $kunci = Cache::lock('cron:data:freshness-check', 60);
        $this->assertTrue($kunci->get());

        try {
            $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'data-freshness-check'))
                ->assertOk()
                ->assertJson(['tugas' => 'data:freshness-check', 'keadaan' => 'dilewati']);
        } finally {
            $kunci->release();
        }

        $this->assertSame(0, ScheduledTaskRun::count());
    }

    public function test_a_finished_run_releases_its_lock(): void
    {
        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'data-freshness-check'))->assertOk();
        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'data-freshness-check'))
            ->assertOk()
            ->assertJson(['keadaan' => 'berhasil']);

        $this->assertSame(2, ScheduledTaskRun::count());
    }

    /**
     * Rute ini berada di grup api: tanpa sesi, tanpa cookie, dan dengan batas laju sendiri.
     */
    public function test_the_route_is_rate_limited_and_outside_the_session_group(): void
    {
        $middleware = app('router')->getRoutes()->getByName('api.cron')->gatherMiddleware();

        $this->assertContains('api', $middleware);
        $this->assertContains('throttle:10,1', $middleware);
        $this->assertNotContains('web', $middleware);
    }

    public function test_every_monitored_task_has_a_slug_that_maps_back_to_it(): void
    {
        foreach (array_keys(SchedulerHealthService::TUGAS) as $kunci) {
            $this->assertSame($kunci, CronController::tugasUntuk(CronController::slug($kunci)));
        }
    }
}
