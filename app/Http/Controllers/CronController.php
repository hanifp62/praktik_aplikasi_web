<?php

namespace App\Http\Controllers;

use App\Models\ScheduledTaskRun;
use App\Services\SchedulerHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menjalankan satu tugas terjadwal atas panggilan Vercel Cron (DK-12, PRD §154 K24).
 *
 * Di Vercel tidak ada proses `schedule:work` yang hidup terus: instans tidur sesudah lima menit tanpa
 * trafik. vercel.json memanggil rute ini dengan header `Authorization: Bearer <CRON_SECRET>`, lalu rute ini
 * menjalankan perintah artisan yang sama dengan scheduler dan mencatatnya di scheduled_task_runs, supaya
 * halaman kesehatan menilai dengan bukti yang sama di server mana pun.
 *
 * Hanya tugas di SchedulerHealthService::TUGAS yang dapat dipanggil, jadi daftar tugas yang dipantau
 * sekaligus menjadi daftar putih, dan rahasianya diperiksa sebelum tugasnya dicari. Tanpa CRON_SECRET rute
 * ini menolak semua panggilan. Vercel tidak mengulang cron yang gagal dan kadang memicu satu jadwal dua
 * kali, jadi kunci cache mencegah dua jalan bersamaan, sementara perintahnya sendiri aman diulang.
 */
class CronController extends Controller
{
    /**
     * Batas waktu PHP untuk satu tugas, di bawah batas 300 detik fungsi Vercel Hobby, supaya PHP yang berhenti lebih
     * dulu dan galat fatalnya sampai ke log. Jalan seperti itu tidak tercatat di scheduled_task_runs (galat fatal
     * melompati catch dan finally), jadi halaman kesehatan baru menandainya TERLAMBAT sesudah toleransinya, dan kunci
     * jalannya kedaluwarsa sendiri 30 detik sesudah batas ini.
     */
    private const BATAS_DETIK = 270;

    public function __invoke(Request $request, string $tugas): JsonResponse
    {
        $rahasia = (string) config('services.cron.secret');

        if ($rahasia === '') {
            Log::warning('Panggilan cron ditolak karena CRON_SECRET belum diatur.');

            return response()->json(['pesan' => 'Tidak berwenang.'], 401);
        }

        if (! hash_equals('Bearer '.$rahasia, (string) $request->header('Authorization'))) {
            return response()->json(['pesan' => 'Tidak berwenang.'], 401);
        }

        $kunci = self::tugasUntuk($tugas);

        if ($kunci === null) {
            return response()->json(['pesan' => 'Tugas tidak dikenal.'], 404);
        }

        $kunciJalan = Cache::lock('cron:'.$kunci, self::BATAS_DETIK + 30);

        if (! $kunciJalan->get()) {
            return response()->json(['tugas' => $kunci, 'keadaan' => 'dilewati', 'pesan' => 'Jalan sebelumnya belum selesai.']);
        }

        // Hanya di server web. Di konsol, termasuk suite test, batas ini akan berlaku untuk sisa prosesnya.
        if (! app()->runningInConsole()) {
            set_time_limit(self::BATAS_DETIK);
        }

        $mulai = hrtime(true);

        try {
            $kode = Artisan::call($kunci, ['--no-interaction' => true]);
        } catch (Throwable $galat) {
            ScheduledTaskRun::catatGalat($kunci, $galat, self::milidetikSejak($mulai));
            report($galat);

            return response()->json(['tugas' => $kunci, 'keadaan' => 'gagal'], 500);
        } finally {
            $kunciJalan->release();
        }

        $durasi = self::milidetikSejak($mulai);
        ScheduledTaskRun::catatKeluar($kunci, $kode, $durasi);

        Log::log($kode === 0 ? 'info' : 'warning', 'Tugas cron selesai.', [
            'tugas' => $kunci,
            // Membedakan jadwal yang memicu tugas yang sama, misalnya pengingat per zona waktu dan susulannya.
            'jadwal' => $request->header('x-vercel-cron-schedule'),
            'kode' => $kode,
            'durasi_ms' => $durasi,
            'keluaran' => mb_substr(trim(Artisan::output()), 0, 2000),
        ]);

        return response()->json(
            ['tugas' => $kunci, 'keadaan' => $kode === 0 ? 'berhasil' : 'gagal', 'kode' => $kode, 'durasi_ms' => $durasi],
            $kode === 0 ? 200 : 500,
        );
    }

    /**
     * Slug URL sebuah tugas: nama perintah artisan dengan titik dua diganti tanda hubung.
     */
    public static function slug(string $kunci): string
    {
        return str_replace(':', '-', $kunci);
    }

    /**
     * Kunci tugas di SchedulerHealthService::TUGAS untuk sebuah slug, atau null bila tidak dikenal.
     */
    public static function tugasUntuk(string $slug): ?string
    {
        foreach (array_keys(SchedulerHealthService::TUGAS) as $kunci) {
            if (self::slug($kunci) === $slug) {
                return $kunci;
            }
        }

        return null;
    }

    private static function milidetikSejak(int $mulai): int
    {
        return (int) round((hrtime(true) - $mulai) / 1_000_000);
    }
}
