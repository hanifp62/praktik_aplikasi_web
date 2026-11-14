<?php

namespace Tests\Feature;

use App\Services\SchedulerHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Jam di halaman kesehatan penjadwal adalah jam tugas itu benar-benar berjalan (temuan audit W9).
 *
 * Penjadwal membaca ekspresi cron dalam zona aplikasi, dan config/app.php menetapkan UTC. Label
 * lama menulis jam ekspresinya dengan akhiran WIB, jadi admin membaca 06.00 WIB untuk tugas yang
 * berjalan pukul 13.00 WIB. Label sekarang diperiksa terhadap ekspresi setiap tugas harian yang
 * diubah ke WIB, supaya mengganti jadwal tanpa mengganti labelnya gagal di sini.
 */
class ScheduleLabelTest extends TestCase
{
    public function test_each_daily_task_names_the_wib_hour_it_really_runs(): void
    {
        $diperiksa = 0;

        foreach (app(Schedule::class)->events() as $event) {
            [$menit, $jam] = explode(' ', $event->expression);
            $kunci = collect(array_keys(SchedulerHealthService::TUGAS))->first(fn (string $k) => str_contains((string) $event->command, $k));

            if ($kunci === null || $jam === '*') {
                continue;
            }

            foreach (explode(',', $jam) as $satuJam) {
                $wib = Carbon::createFromTime((int) $satuJam, (int) $menit, 0, $event->timezone ?? config('app.timezone'))
                    ->setTimezone('Asia/Jakarta')
                    ->format('H.i');

                $this->assertStringContainsString($wib, SchedulerHealthService::TUGAS[$kunci]['jadwal'], "Label {$kunci} tidak menyebut {$wib} WIB.");
                $diperiksa++;
            }
        }

        $this->assertGreaterThanOrEqual(4, $diperiksa, 'Prakiraan dua kali, kesegaran status, dan pemangkasan tautan diperiksa.');
    }
}
