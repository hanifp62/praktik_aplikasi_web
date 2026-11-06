<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\JalurUntukAnda;
use App\Services\Dashboard\KabarBeranda;
use App\Services\Dashboard\RingkasanDasbor;
use App\Services\Dashboard\TanggaTanjakan;
use App\Services\Dashboard\TenggatTrip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45). Isinya disusun RingkasanDasbor dan kelas
 * bagian Beranda; halaman React hanya memilih tampilan dari fasenya.
 *
 * Setiap bagian adalah prop malas (closure), jadi muat ulang parsial yang tidak memintanya,
 * misalnya lembar Pengingat yang hanya meminta daftarPengingat, tidak menghitung Beranda
 * (ruling W2-6b).
 */
class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        RingkasanDasbor $ringkasan,
        TenggatTrip $tenggat,
        KabarBeranda $kabar,
        JalurUntukAnda $jalurLain,
        TanggaTanjakan $tangga,
    ): Response {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'title' => 'Beranda',
            'fase' => fn () => $ringkasan->props($user),
            'tenggat' => fn () => $tenggat->untuk($ringkasan->konteks($user)),
            'kabar' => fn () => $kabar->untuk($ringkasan->konteks($user)),
            'jalurLain' => fn () => $jalurLain->untuk($ringkasan->konteks($user)),
            'tangga' => fn () => $tangga->untuk($ringkasan->konteks($user)),
        ]);
    }
}
