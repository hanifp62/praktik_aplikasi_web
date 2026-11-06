<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Services\Trips\PendakianAktif;
use App\Services\Trips\PintuMendaki;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pintu tab Mendaki (IA v2, DK-25; spec v3 bagian 6.12).
 *
 * Pendakian yang sedang berjalan dibuka langsung: pendaki yang mengetuk tab ini di jalur
 * butuh mode lapangannya, bukan daftar. Pemilihnya sama dengan strip dan Beranda
 * (PendakianAktif, ruling W6-R2). Tanpa pendakian berjalan, halaman menampilkan pratinjau
 * hari-H trip terdekat yang dapat dimulai (PintuMendaki). Semua kueri milik pengguna yang masuk.
 */
class TrackController extends Controller
{
    public function __invoke(Request $request, PendakianAktif $aktif, PintuMendaki $pintu): Response|RedirectResponse
    {
        $user = $request->user();
        $berjalan = $aktif->untuk($user);

        if ($berjalan !== null) {
            return redirect()->route('trips.hike', $berjalan['id']);
        }

        return Inertia::render('Track/Index', [
            'title' => 'Mendaki',
            ...$pintu->untuk($user),
        ]);
    }
}
