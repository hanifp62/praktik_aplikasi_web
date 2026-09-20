<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Manifest dan service worker disajikan lewat route, bukan sebagai berkas statis, supaya
 * URL asetnya mengikuti manifest Vite yang hash-nya berubah setiap build (PRD §106).
 */
class ProgressiveWebAppController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => 'Rencana Pendakian',
            'short_name' => 'Pendakian',
            'description' => 'Cari tahu jalur mana yang sesuai kemampuan Anda, sebelum berangkat.',
            'lang' => 'id',
            'display' => 'standalone',
            'orientation' => 'portrait',

            // Dibuka langsung ke dasbor: pengguna yang memasang aplikasi ini sudah
            // melewati halaman perkenalan.
            'start_url' => '/dashboard',
            'scope' => '/',
            'background_color' => '#ffffff',
            'theme_color' => '#047857',
            'icons' => [
                ['src' => '/icons/app-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/app-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/app-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function serviceWorker(): Response
    {
        return response(view('sw')->render(), 200, [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
