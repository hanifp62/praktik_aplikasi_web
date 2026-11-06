<?php

namespace Tests\Feature;

use App\Http\Controllers\CronController;
use App\Services\SchedulerHealthService;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * vercel.json dibaca sebagai data (DK-12, PRD §154).
 *
 * Vercel membangun Dockerfile menjadi container-backed Function dan memicu tugas terjadwal lewat Vercel Cron, karena di
 * sana tidak ada schedule:work. Vercel Hobby hanya menerima ekspresi cron yang berjalan sekali sehari; ekspresi yang
 * lebih sering menggagalkan deploy. Test ini menjaga agar jadwal di vercel.json mengikuti routes/console.php dan jam
 * jatuh tempo pengingat, dan agar setiap path cron menunjuk tugas yang memang dapat dipicu CronController.
 */
class VercelConfigTest extends TestCase
{
    public function test_the_app_runs_as_one_container_service_built_from_the_dockerfile_in_singapore(): void
    {
        $konfigurasi = $this->konfigurasi();

        $this->assertSame(['sin1'], $konfigurasi['regions'], 'Supabase ada di ap-southeast-1; fungsi di region lain menambah ratusan milidetik ke setiap kueri.');
        $this->assertSame(['**' => false, 'main' => true], $konfigurasi['git']['deploymentEnabled'], 'Hanya main yang di-deploy otomatis; preview cabang lain memakai rahasia produksi dan slot image VCR.');
        $this->assertSame(['app' => ['root' => '.', 'entrypoint' => 'Dockerfile', 'runtime' => 'container']], $konfigurasi['services']);
        $this->assertFileExists(base_path('Dockerfile'));
        $this->assertSame([['source' => '/(.*)', 'destination' => ['service' => 'app']]], $konfigurasi['rewrites']);
    }

    public function test_every_cron_path_is_the_cron_route_of_a_monitored_task(): void
    {
        foreach ($this->konfigurasi()['crons'] as $cron) {
            $slug = Str::after($cron['path'], '/api/cron/');

            $this->assertNotNull(CronController::tugasUntuk($slug), "{$cron['path']} tidak menunjuk tugas yang dipantau.");
            $this->assertSame($cron['path'], parse_url(route('api.cron', $slug), PHP_URL_PATH));
        }
    }

    /**
     * Vercel Hobby menolak ekspresi yang berjalan lebih dari sekali sehari, dan deploy gagal karenanya.
     */
    public function test_every_schedule_runs_at_most_once_a_day(): void
    {
        foreach ($this->konfigurasi()['crons'] as $cron) {
            $this->assertMatchesRegularExpression('/^([0-9]|[1-5][0-9]) ([0-9]|1[0-9]|2[0-3]) \* \* \*$/', $cron['schedule'], "{$cron['schedule']} bukan jadwal harian.");
        }
    }

    public function test_every_monitored_task_is_triggered_on_vercel(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(SchedulerHealthService::TUGAS), array_keys($this->jamCron()));
    }

    /**
     * Tugas berjam tetap di routes/console.php (cuaca pukul 06 dan 18, kesegaran pukul 07) berjalan pada jam yang
     * sama di Vercel. Jam di ekspresi dibaca dalam zona jadwalnya (zona event, atau zona aplikasi) lalu diubah ke UTC,
     * zona tempat Vercel Cron membaca vercel.json, supaya jadwal yang diberi zona lain tidak lolos dengan jam yang sama.
     */
    public function test_fixed_hour_tasks_keep_the_hours_of_routes_console(): void
    {
        $jamCron = $this->jamCron();
        $diperiksa = 0;

        foreach (app(Schedule::class)->events() as $event) {
            [$menit, $jam] = explode(' ', $event->expression);

            if ($jam === '*') {
                continue;
            }

            $kunci = $this->kunciUntuk((string) $event->command);
            $zona = $event->timezone ?: config('app.timezone');
            $jamUtc = array_map(
                fn (string $satu): int => CarbonImmutable::parse(sprintf('2026-10-13 %02d:00', (int) $satu), $zona)->utc()->hour,
                explode(',', $jam),
            );

            $this->assertSame('0', $menit, "{$kunci} tidak berjalan di awal jam.");
            $this->assertEqualsCanonicalizing($jamUtc, $jamCron[$kunci], "Jam {$kunci} di vercel.json berbeda dari routes/console.php.");
            $diperiksa++;
        }

        $this->assertSame(2, $diperiksa, 'Cuaca dan kesegaran data seharusnya berjam tetap.');
    }

    /**
     * Pengingat (FR-24) jatuh pukul local_hour di zona gunungnya. Server memeriksanya tiap jam; di Vercel Hobby ia
     * dipicu pada jam jatuh tempo setiap zona Indonesia (dalam UTC selalu awal jam, karena tidak ada musim panas),
     * ditambah satu jalan susulan yang masih jatuh pada tanggal yang sama di setiap zona. Tanpa susulan itu, satu
     * pengiriman cron yang hilang menghapus pengingat H-1 WIB, karena jalan berikutnya sudah hari keberangkatan.
     */
    public function test_reminders_run_at_every_due_hour_plus_one_same_day_catch_up(): void
    {
        $jamPengingat = $this->jamCron()['trips:kirim-pengingat'];
        $jamTempo = array_map(fn (string $zona): int => $this->tempoPengingat($zona)->utc()->hour, array_keys(Timezone::SUPPORTED));

        $this->assertSame([], array_values(array_diff($jamTempo, $jamPengingat)), 'Ada zona yang jam jatuh temponya tidak dipicu.');

        $susulan = array_values(array_diff($jamPengingat, $jamTempo));
        $this->assertCount(1, $susulan, 'Harus ada tepat satu jalan susulan.');

        foreach (array_keys(Timezone::SUPPORTED) as $zona) {
            $tempo = $this->tempoPengingat($zona);
            $jalan = $tempo->utc()->setTime($susulan[0], 0);

            if ($jalan->lessThanOrEqualTo($tempo)) {
                $jalan = $jalan->addDay();
            }

            $this->assertSame($tempo->toDateString(), $jalan->setTimezone($zona)->toDateString(), "Jalan susulan jatuh di hari lain di {$zona}.");
        }
    }

    /**
     * Toleransi halaman kesehatan untuk pengingat di Vercel tidak boleh lebih sempit dari jarak terpanjang antar-jalan
     * ditambah satu jam (Hobby memicu kapan saja dalam jam yang dijadwalkan), atau tanda TERLAMBAT menyala palsu.
     */
    public function test_the_daily_reminder_tolerance_covers_the_longest_gap_between_runs(): void
    {
        $jam = $this->jamCron()['trips:kirim-pengingat'];
        sort($jam);

        $jarak = array_map(fn (int $i): int => ($jam[($i + 1) % count($jam)] - $jam[$i] + 24) % 24 ?: 24, array_keys($jam));

        config(['hiking.penjadwal.hanya_harian' => true]);
        $toleransi = app(SchedulerHealthService::class)->tugas()['trips:kirim-pengingat']['toleransi_jam'];

        $this->assertGreaterThanOrEqual(max($jarak) + 1, $toleransi);
    }

    /**
     * @return array<string, mixed>
     */
    private function konfigurasi(): array
    {
        return json_decode(File::get(base_path('vercel.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Jam UTC tiap tugas di vercel.json, dikelompokkan per kunci tugas.
     *
     * @return array<string, list<int>>
     */
    private function jamCron(): array
    {
        $jam = [];

        foreach ($this->konfigurasi()['crons'] as $cron) {
            $kunci = (string) CronController::tugasUntuk(Str::after($cron['path'], '/api/cron/'));
            $jam[$kunci][] = (int) explode(' ', $cron['schedule'])[1];
        }

        return $jam;
    }

    private function tempoPengingat(string $zona): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-13', $zona)->setTime((int) config('hiking.reminders.local_hour'), 0);
    }

    private function kunciUntuk(string $perintah): string
    {
        foreach (array_keys(SchedulerHealthService::TUGAS) as $kunci) {
            if (str_contains($perintah, $kunci)) {
                return $kunci;
            }
        }

        $this->fail("Tugas terjadwal {$perintah} tidak dipantau.");
    }
}
