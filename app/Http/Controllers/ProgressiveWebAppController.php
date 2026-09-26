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
        // Nama dari config('app.name'), bukan ditulis di sini: tiga nama produk yang
        // berbeda pernah hidup berdampingan di runtime (F11), dan nama merek final
        // masih menunggu keputusan pemilik (DK-13). Satu sumber, satu perubahan.
        //
        // Orientasi 'any', bukan 'portrait': mengunci orientasi gagal WCAG 1.3.4, dan
        // pendaki yang memasang ponsel melintang di stang atau tas tidak boleh dipaksa
        // memutar kepala (F12, DK-19).
        return response()->json([
            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'description' => config('brand.description'),
            'lang' => 'id',
            'display' => 'standalone',
            'orientation' => 'any',

            // Dibuka langsung ke dasbor: pengguna yang memasang aplikasi ini sudah
            // melewati halaman perkenalan.
            'start_url' => '/dashboard',
            'scope' => '/',
            'background_color' => config('brand.background_color.light'),
            'theme_color' => config('brand.theme_color.light'),

            // Ikon maskable berkas tersendiri: OS memotong ikon maskable menjadi
            // lingkaran atau squircle, dan tanda yang digambar sampai tepi pada ikon
            // 'any' akan terpotong di sana. Versi maskable menyisakan zona aman 40%.
            'icons' => [
                ['src' => '/icons/app-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/app-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/app-512-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
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
