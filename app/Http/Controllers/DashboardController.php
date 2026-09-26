<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\RingkasanDasbor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dasbor yang mengikuti posisi pengguna dalam alur: pendakian yang sedang berjalan,
 * trip terdekat, atau ajakan memulai. Isinya dihitung RingkasanDasbor; halaman React
 * hanya memilih cabang dari `keadaan`.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'title' => 'Dasbor',
            ...(new RingkasanDasbor($request->user()))->props(),
        ]);
    }
}
