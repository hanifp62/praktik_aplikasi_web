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
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
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
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",

            // MapLibre menjalankan pengurai tile di web worker yang dibuat dari blob.
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
