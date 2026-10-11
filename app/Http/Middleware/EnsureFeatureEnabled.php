<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rute fitur yang menunggu keputusan pemilik menjawab 404 selama sakelarnya mati (ruling W6-R3
 * dan W6-R4): paket lapangan luring (hiking.field_pack, Q10 dan Q14) dan alat keselamatan trip
 * (hiking.field_safety, Q14). Diperiksa sebelum otorisasi dan validasi, jadi rute yang mati
 * tidak membocorkan keberadaannya lewat 403 atau galat isian.
 *
 * Juga sebelum batas laju dan pengikatan model rute: bootstrap/app.php menaruh middleware ini
 * di daftar prioritas tepat sebelum ThrottleRequests (pra-tinjau Tugas 7 PT7-17). Tanpa itu,
 * Laravel menjalankan ThrottleRequests lebih dulu karena ia ada di daftar prioritas dan
 * middleware ini tidak, sehingga kiriman ke-31 dalam semenit menjawab 429, bukan 404.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $fitur): Response
    {
        abort_unless(in_array($fitur, ['field_pack', 'field_safety'], true) && config("hiking.{$fitur}.enabled") === true, 404);

        return $next($request);
    }
}
