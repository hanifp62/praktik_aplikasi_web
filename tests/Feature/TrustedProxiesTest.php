<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Proxy tepercaya diatur lewat TRUSTED_PROXIES (deploy Vercel, vercel.json). Tanpa nilai, header X-Forwarded-*
 * diabaikan, jadi di lokal dan test tidak ada klien yang dapat memalsukan skema atau IP-nya. Dengan '*', skema https
 * dan IP pengguna dari proxy platform dipakai, supaya cookie aman dan batas laju per IP benar.
 */
class TrustedProxiesTest extends TestCase
{
    /**
     * Nilai APP_ENV sebelum test mengubahnya: getenv, $_ENV, dan $_SERVER.
     *
     * @var array{0: string|false, 1: mixed, 2: mixed}|null
     */
    private ?array $appEnvAsli = null;

    protected function tearDown(): void
    {
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
        $this->pulihkanAppEnv();
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
     * docker/start.sh meng-cache konfigurasi saat kontainer mulai, dan sesudah config:cache berkas .env tidak dimuat
     * lagi. Proxy tepercaya karena itu dibaca dari konfigurasi, bukan env() di bootstrap/app.php. Di sini
     * konfigurasinya benar-benar di-cache dan nilainya hanya ada di cache, tidak di env; proxy harus tetap dipercaya.
     */
    public function test_the_proxy_setting_is_read_from_the_cached_configuration(): void
    {
        // Path relatif terhadap akar proyek: Laravel hanya menganggap APP_CONFIG_CACHE absolut bila diawali / atau \.
        $cache = 'storage/framework/testing/konfigurasi-'.uniqid().'.php';
        $konfigurasi = config()->all();
        $konfigurasi['app']['trusted_proxies'] = '*';
        File::ensureDirectoryExists(base_path('storage/framework/testing'));
        File::put(base_path($cache), '<?php return '.var_export($konfigurasi, true).';'.PHP_EOL);

        try {
            putenv('APP_CONFIG_CACHE='.$cache);
            $_ENV['APP_CONFIG_CACHE'] = $_SERVER['APP_CONFIG_CACHE'] = $cache;
            $this->refreshApplication();

            $this->assertTrue($this->app->configurationIsCached(), 'Konfigurasi uji tidak termuat dari cache.');
            $this->assertSame(['aman' => true, 'ip' => '203.0.113.9'], $this->mintaLewatProxy());
        } finally {
            putenv('APP_CONFIG_CACHE');
            unset($_ENV['APP_CONFIG_CACHE'], $_SERVER['APP_CONFIG_CACHE']);
            File::delete(base_path($cache));
        }
    }

    /**
     * Produksi selalu dilayani lewat https: tautan dan URL aset dibuat https walau permintaan sampai ke aplikasi lewat
     * http tanpa X-Forwarded-Proto. Tanpanya, aset yang termuat lewat http diblokir peramban dan halaman kosong.
     */
    public function test_production_always_generates_https_urls(): void
    {
        $this->jadikanProduksi();

        Route::get('/_uji-url', fn () => ['url' => url('/login')]);

        $url = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->get('/_uji-url')->assertOk()->json('url');

        $this->assertStringStartsWith('https://', $url);
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

    private function jadikanProduksi(): void
    {
        $this->appEnvAsli = [getenv('APP_ENV'), $_ENV['APP_ENV'] ?? null, $_SERVER['APP_ENV'] ?? null];

        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
        $this->refreshApplication();
    }

    private function pulihkanAppEnv(): void
    {
        if ($this->appEnvAsli === null) {
            return;
        }

        [$putenv, $env, $server] = $this->appEnvAsli;

        putenv($putenv === false ? 'APP_ENV' : 'APP_ENV='.$putenv);

        if ($env === null) {
            unset($_ENV['APP_ENV']);
        } else {
            $_ENV['APP_ENV'] = $env;
        }

        if ($server === null) {
            unset($_SERVER['APP_ENV']);
        } else {
            $_SERVER['APP_ENV'] = $server;
        }

        $this->appEnvAsli = null;
    }
}
