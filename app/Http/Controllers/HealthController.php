<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pemeriksaan kesiapan basis data publik (`/api/health`).
 *
 * Berbeda dari `/up` yang hanya memeriksa proses PHP hidup, endpoint ini menjalankan
 * kueri ringan `SELECT 1` agar kesalahan konfigurasi koneksi produksi langsung tampak
 * sebagai 503 tanpa membocorkan DSN, host, kredensial, atau pesan galat internal.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable $galat) {
            Log::warning('Pemeriksaan kesiapan basis data gagal.', [
                'kelas' => $galat::class,
            ]);

            return response()
                ->json(['status' => 'unavailable', 'db' => false], 503)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()
            ->json(['status' => 'ok', 'db' => true])
            ->header('Cache-Control', 'no-store, private');
    }
}
