<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ScheduledTaskRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\SchedulerHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman kesehatan sistem.
 *
 * Halaman ini ada untuk kegagalan yang tidak pernah menghasilkan galat, jadi yang diuji
 * adalah apakah ketiadaan itu benar-benar terbaca sebagai masalah, bukan apakah
 * halamannya terbuka. Halamannya kini React: keadaan dibaca dari props, teks panduan
 * yang dirender klien dari sumber komponennya.
 */
class SystemHealthPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    public function test_a_scheduler_that_never_ran_is_announced_not_left_blank(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Health')
                ->where('title', 'Kesehatan Sistem')
                ->where('perluDiurus', true)
                ->where('tugas.0.keadaan', SchedulerHealthService::BELUM_PERNAH)
                ->where('tugas.0.keadaanLabel', 'Belum pernah berjalan'));

        $this->assertStringContainsString('schedule:run', File::get(resource_path('js/pages/Admin/Health.tsx')));
    }

    public function test_a_healthy_scheduler_raises_no_alarm(): void
    {
        foreach (array_keys(SchedulerHealthService::TUGAS) as $tugas) {
            ScheduledTaskRun::create([
                'task' => $tugas,
                'status' => ScheduledTaskRun::SUCCESS,
                'ran_at' => now()->subHour(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $page->where('perluDiurus', false)->has('tugas', count(SchedulerHealthService::TUGAS));

                foreach (range(0, count(SchedulerHealthService::TUGAS) - 1) as $i) {
                    $page->where("tugas.{$i}.keadaanLabel", 'Berjalan normal');
                }
            });
    }

    public function test_a_stopped_scheduler_is_named_as_overdue(): void
    {
        ScheduledTaskRun::create([
            'task' => 'weather:refresh',
            'status' => ScheduledTaskRun::SUCCESS,
            'ran_at' => now()->subDays(3),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('tugas.0.keadaanLabel', 'Terlambat'));
    }

    /**
     * Tugas yang baru didaftarkan dan belum pernah berjalan di server ini menyalakan
     * peringatan, tidak ikut terbaca normal hanya karena dua tugas lainnya sehat.
     */
    public function test_the_reminder_task_that_never_ran_raises_the_alarm(): void
    {
        foreach (['weather:refresh', 'data:freshness-check'] as $tugas) {
            ScheduledTaskRun::create([
                'task' => $tugas,
                'status' => ScheduledTaskRun::SUCCESS,
                'ran_at' => now()->subHour(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('perluDiurus', true)
                ->where('tugas.2.kunci', 'trips:kirim-pengingat')
                ->where('tugas.2.judul', 'Pengiriman pengingat perjalanan')
                ->where('tugas.2.keadaanLabel', 'Belum pernah berjalan'));

        $this->assertStringContainsString('pengingat perjalanan berhenti terkirim', File::get(resource_path('js/pages/Admin/Health.tsx')));
    }

    public function test_a_hiker_cannot_open_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.health'))
            ->assertForbidden();
    }

    /**
     * Ringkasan kesehatan data membaca gerbang publikasi yang sama dengan penerbitan
     * (R-022): jalur tanpa pos dan tanpa status resmi terhitung belum lengkap, beserta
     * kekurangannya dan tautan ke halaman jalur itu.
     */
    public function test_the_data_summary_lists_what_each_trail_still_lacks(): void
    {
        $belum = Trail::factory()->unpublished()->create(['name' => 'Jalur Belum Lengkap']);

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kesehatanData.jalurBelumLengkap', 1)
                ->where('kesehatanData.daftar.0.nama', 'Jalur Belum Lengkap')
                ->where('kesehatanData.daftar.0.kekurangan', $belum->fresh()->publishabilityReport())
                ->where('kesehatanData.daftar.0.posUrl', route('trails.checkpoints', $belum))
                ->where('kesehatanData.sisa', 0));
    }

    /**
     * Tanpa PostGIS geometri tidak dapat diperiksa. Angkanya harus "tidak diketahui",
     * bukan nol yang terbaca seolah semua jalur sudah bergeometri (R-006).
     */
    public function test_geometry_that_cannot_be_checked_is_unknown_not_zero(): void
    {
        $this->assertFalse(Trail::spatialSupported(), 'Test ini berjalan di SQLite.');

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kesehatanData.tanpaGeometri', null));
    }

    public function test_the_data_summary_does_not_grow_with_the_number_of_trails(): void
    {
        $this->actingAs($this->admin());
        Trail::factory()->count(2)->unpublished()->create();
        $this->get(route('admin.health'));
        $sedikit = $this->hitungQuery();

        Trail::factory()->count(8)->unpublished()->create();

        $this->assertSame($sedikit, $this->hitungQuery());
    }

    private function hitungQuery(): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get(route('admin.health'));

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    /**
     * R-024 dan HANDOVER §6: suite berjalan di SQLite yang migrasinya selalu lengkap,
     * sehingga basis data produksi yang tertinggal tidak pernah terlihat oleh test. Pada
     * 21 September 2026 halaman Jelajahi galat di tangan pemilik sementara seluruh test
     * hijau. Halaman kesehatan membaca status migrasi dari basis data yang dipakai.
     */
    public function test_a_pending_migration_is_named_on_the_health_page(): void
    {
        $terakhir = DB::table('migrations')->orderByDesc('id')->value('migration');
        DB::table('migrations')->where('migration', $terakhir)->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('migrasi.tersedia', true)
                ->where('migrasi.tertunda', [$terakhir]));
    }

    public function test_a_fully_migrated_database_reports_nothing_pending(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('migrasi.tersedia', true)
                ->where('migrasi.tertunda', []));

        $this->assertStringContainsString('php artisan migrate', File::get(resource_path('js/pages/Admin/Health.tsx')));
    }
}
