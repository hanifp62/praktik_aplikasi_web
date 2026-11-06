<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk seluruh respons web.
 *
 * Tanpa ini halaman mana pun dapat dibingkai situs lain dan tombolnya diklik pengguna
 * tanpa ia sadari, termasuk tombol hapus akun.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        // Respons yang menyetel kebijakannya sendiri tidak ditimpa: halaman tautan bagikan
        // memakai no-referrer karena tokennya ada di URL (T8-M3 tinjauan akhir W3).
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Content-Security-Policy', $this->csp());

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function csp(): string
    {
        $peta = implode(' ', $this->hostPeta());

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            trim("img-src 'self' data: blob: {$peta}"),
            trim("connect-src 'self' {$peta}"),

            // Alpine mengevaluasi ekspresi dengan new Function(), dan Livewire menyuntikkan
            // skrip inline. Keduanya menuntut unsafe-eval dan unsafe-inline; mengaku apa
            // adanya lebih berguna daripada menulis CSP ketat yang memutus antarmuka.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",

            // Huruf dihosting sendiri sejak gelombang 1 v3 (DK-38): style-src dan
            // font-src tidak lagi mengizinkan asal luar, jadi huruf yang ditambahkan
            // dari layanan lain diblokir CSP alih-alih diam-diam menjadi ketergantungan.
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",

            // MapLibre 6 menjalankan pengurai tile di web worker dari berkas build sendiri ('self').
            // blob: peninggalan MapLibre 4, yang membuat pekerja dari URL blob bundel. Build produksi
            // MapLibre 6 tidak lagi membutuhkannya; pencabutannya menunggu uji peramban di mode
            // lapangan luring (tinjauan upgrade M-4).
            "worker-src 'self' blob:",
        ]);
    }

    /**
     * Asal penyedia tile diambil dari konfigurasi agar CSP ikut berubah ketika operator
     * mengganti penyedianya, bukan diam-diam memutus peta.
     *
     * @return array<int, string>
     */
    private function hostPeta(): array
    {
        $sumber = array_filter(array_merge(
            (array) config('hiking.map.raster_tiles', []),
            [config('hiking.map.style_url')]
        ));

        $asal = [];

        foreach ($sumber as $url) {
            $bagian = parse_url((string) $url);

            if (isset($bagian['scheme'], $bagian['host'])) {
                $asal[] = $bagian['scheme'].'://'.$bagian['host'];
            }
        }

        return array_values(array_unique($asal));
    }
}
