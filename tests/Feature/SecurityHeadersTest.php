<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Aplikasi ini sebelumnya tidak mengirim satu pun header keamanan. Tanpa
 * X-Frame-Options atau frame-ancestors, halaman mana pun dapat dibingkai situs lain
 * dan tombolnya diklik pengguna tanpa ia sadari, termasuk tombol hapus akun.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function header(): array
    {
        return [
            'nosniff' => ['X-Content-Type-Options', 'nosniff'],
            'bingkai' => ['X-Frame-Options', 'DENY'],
            'referrer' => ['Referrer-Policy', 'strict-origin-when-cross-origin'],
        ];
    }

    #[DataProvider('header')]
    public function test_a_page_carries_the_header(string $nama, string $nilai): void
    {
        $this->get('/login')->assertOk()->assertHeader($nama, $nilai);
    }

    public function test_the_page_cannot_be_framed_by_another_site(): void
    {
        $csp = $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    /**
     * Mode pendakian membaca posisi pengguna di lapangan, jadi geolocation harus tetap
     * diizinkan untuk asal sendiri. Kamera dan mikrofon tidak pernah dipakai.
     */
    public function test_geolocation_stays_available_while_unused_sensors_are_closed(): void
    {
        $policy = $this->get('/login')->assertOk()->headers->get('Permissions-Policy');

        $this->assertStringContainsString('geolocation=(self)', $policy);
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
    }

    /**
     * Host tile diambil dari konfigurasi, bukan ditulis tetap, supaya CSP tidak diam-diam
     * memutus peta ketika operator mengganti penyedia tile.
     */
    public function test_the_configured_tile_host_is_allowed(): void
    {
        config(['hiking.map.raster_tiles' => ['https://tile.contoh-peta.id/{z}/{x}/{y}.png']]);

        $csp = $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://tile.contoh-peta.id', $csp);
    }

    /**
     * Huruf dimuat dari asal aplikasi sendiri (DK-38).
     *
     * Menggantikan test_the_font_host_the_layouts_actually_use_is_allowed, yang menuntut
     * CSP mengizinkan Bunny Fonts. Sejak gelombang 1 v3 huruf dihosting sendiri, jadi
     * niatnya dibalik: CSP tidak mengizinkan asal huruf luar mana pun, sehingga huruf yang
     * ditambahkan dari layanan lain diblokir alih-alih diam-diam menjadi ketergantungan.
     */
    public function test_fonts_load_only_from_our_own_origin(): void
    {
        $csp = $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/font-src 'self' data:(;|$)/", $csp);
        $this->assertMatchesRegularExpression("/style-src 'self' 'unsafe-inline'(;|$)/", $csp);
        $this->assertStringNotContainsString('fonts.bunny.net', $csp);
    }

    public function test_an_authenticated_page_carries_the_headers_too(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    /**
     * HSTS hanya berarti di atas HTTPS. Mengirimnya pada koneksi lokal yang polos tidak
     * menambah keamanan dan menyulitkan pengembangan.
     */
    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        $this->get('/login')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
    }
}
