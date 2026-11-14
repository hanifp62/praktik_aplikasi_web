<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Proxy tepercaya (T8-M2 tinjauan akhir W3, dititipkan ke W9; diselaraskan dengan 7a27211).
 *
 * Batas laju per alamat (tautan bagikan, pendaftaran, atur ulang sandi) membaca alamat
 * pengunjung. Di belakang penyeimbang beban, tanpa daftar proxy tepercaya, alamat itu adalah
 * alamat proxy, sehingga semua tamu berbagi satu kuota. Mempercayai X-Forwarded-For dari siapa
 * pun justru membiarkan pengunjung memalsukan alamatnya. Karena itu daftarnya kosong secara
 * bawaan dan diisi lewat TRUSTED_PROXIES (config('app.trusted_proxies'), dibaca AppServiceProvider
 * ke TrustProxies::at) hanya di lingkungan yang memang di belakang proxy.
 */
class TrustedProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_a_forwarded_address_is_ignored_while_no_proxy_is_trusted(): void
    {
        $this->aturProxy(null);

        $this->assertSame('10.0.0.5', $this->alamatTerbaca('10.0.0.5', '203.0.113.7'));
    }

    public function test_a_listed_proxy_passes_on_the_visitor_address(): void
    {
        $this->aturProxy('10.0.0.5,10.0.0.6');

        $this->assertSame('203.0.113.7', $this->alamatTerbaca('10.0.0.5', '203.0.113.7'));
        $this->assertSame('10.0.0.9', $this->alamatTerbaca('10.0.0.9', '203.0.113.7'), 'Alamat yang tidak terdaftar tetap tidak dipercaya.');
    }

    /**
     * Penyedia seperti Vercel tidak punya daftar alamat proxy tetap; "*" mempercayai pengirim
     * langsung, yang di sana selalu tepi jaringan penyedia.
     */
    public function test_a_wildcard_trusts_the_sender_in_front_of_the_application(): void
    {
        $this->aturProxy('*');

        $this->assertSame('203.0.113.7', $this->alamatTerbaca('10.0.0.5', '203.0.113.7'));
    }

    private function aturProxy(?string $nilai): void
    {
        TrustProxies::flushState();
        config(['app.trusted_proxies' => $nilai]);

        if (filled($nilai)) {
            TrustProxies::at($nilai);
        }
    }

    private function alamatTerbaca(string $pengirim, string $diteruskan): string
    {
        Route::get('/_uji/alamat', fn (Request $request) => (string) $request->ip());

        return (string) $this->withServerVariables(['REMOTE_ADDR' => $pengirim])
            ->withHeaders(['X-Forwarded-For' => $diteruskan])
            ->get('/_uji/alamat')
            ->getContent();
    }
}
