<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Proxy tepercaya diatur lewat TRUSTED_PROXIES (deploy Vercel, vercel.json). Tanpa nilai, header X-Forwarded-*
 * diabaikan, jadi di lokal dan test tidak ada klien yang dapat memalsukan skema atau IP-nya. Dengan '*', skema https
 * dan IP pengguna dari proxy platform dipakai, supaya cookie aman dan batas laju per IP benar.
 */
class TrustedProxiesTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_forwarded_headers_are_ignored_without_trusted_proxies(): void
    {
        $this->assertSame(['aman' => false, 'ip' => '10.0.0.5'], $this->mintaLewatProxy());
    }

    public function test_the_platform_proxy_is_trusted_when_configured(): void
    {
        putenv('TRUSTED_PROXIES=*');
        $_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = '*';
        $this->refreshApplication();

        $this->assertSame(['aman' => true, 'ip' => '203.0.113.9'], $this->mintaLewatProxy());
    }

    /**
     * @return array{aman: bool, ip: string|null}
     */
    private function mintaLewatProxy(): array
    {
        Route::get('/_uji-proxy', fn () => ['aman' => request()->isSecure(), 'ip' => request()->ip()]);

        return $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
            ->get('/_uji-proxy')
            ->assertOk()
            ->json();
    }
}
