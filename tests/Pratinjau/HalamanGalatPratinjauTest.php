<?php

namespace Tests\Pratinjau;

use App\Support\HalamanGalat;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;

/**
 * Halaman galat W9 (ruling W4-6j) untuk dipotret.
 *
 * Kunjungan Inertia menerima halaman React Galat sebagai JSON, yang tidak dapat ditulis
 * sebagai berkas pratinjau. Rute uji di bawah merender komponen dan props yang sama persis
 * (App\Support\HalamanGalat::props(), tanpa props bersama kerangka) sebagai muat penuh.
 * Halaman Blade 429 dipotret dari batas laju sungguhan.
 */
class HalamanGalatPratinjauTest extends PratinjauTestCase
{
    public function test_galat_404(): void
    {
        $this->tulisPratinjau('galat-404', $this->halamanGalat(404), 'Galat');
    }

    /**
     * 429 dengan Retry-After 42 detik, seperti yang dikirim middleware throttle.
     */
    public function test_galat_429(): void
    {
        $this->tulisPratinjau('galat-429', $this->halamanGalat(429, new ThrottleRequestsException('Too Many Attempts.', null, ['Retry-After' => 42])), 'Galat');
    }

    public function test_galat_500(): void
    {
        $this->tulisPratinjau('galat-500', $this->halamanGalat(500), 'Galat');
    }

    public function test_galat_429_muat_penuh(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->get('/_pratinjau-galat/terbatas', fn () => 'isi');

        $this->get(self::ASAL.'/_pratinjau-galat/terbatas')->assertOk();
        $respons = $this->get(self::ASAL.'/_pratinjau-galat/terbatas')->assertStatus(429)->assertSee('Terlalu banyak permintaan dalam waktu singkat');
        $html = (string) $respons->getContent();

        $this->assertStringContainsString(self::ASAL.'/build/assets/', $html, 'Aset tidak dirujuk dari asal server statis.');

        File::ensureDirectoryExists(public_path('_pratinjau'));
        File::put(public_path('_pratinjau/galat-429-muat-penuh.html'), $html);
    }

    private function halamanGalat(int $kode, ?ThrottleRequestsException $galat = null): TestResponse
    {
        Route::middleware('web')->get('/_pratinjau-galat/{kode}', function (Request $request, int $kode) use ($galat) {
            Inertia::flushShared();

            return Inertia::render('Galat', HalamanGalat::props($kode, $galat, $request));
        });

        return $this->get(self::ASAL.'/_pratinjau-galat/'.$kode);
    }
}
