<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Identitas merek punya satu sumber.
 *
 * Tiga nama produk pernah hidup berdampingan di runtime: APP_NAME, nama manifest, dan
 * teks header halaman depan (F11, K31). Nama merek final masih keputusan pemilik
 * (DK-13), jadi yang dikunci di sini bukan namanya, melainkan bahwa setiap permukaan
 * membaca nama yang sama dari config('app.name') dan bahwa logo bawaan framework tidak
 * lagi mewakili produk.
 */
class BrandIdentityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nama merek ditetapkan pemilik 24 September 2026. Test ini menjaga agar env atau
     * konfigurasi mana pun tidak diam-diam mengembalikannya ke nama lama.
     */
    public function test_the_brand_name_is_memount(): void
    {
        $this->assertSame('Memount', config('app.name'));
    }

    public function test_the_wordmark_on_every_surface_is_the_configured_name(): void
    {
        $nama = config('app.name');

        $this->assertStringContainsString($nama, $this->get('/')->assertOk()->getContent());
        // Halaman masuk kini React: wordmark di layout tamu membaca props merek.nama.
        $this->get('/login')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('merek.nama', $nama));

        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        $this->assertStringContainsString($nama, $this->actingAs($user)->get(route('trails.index'))->assertOk()->getContent());
    }

    public function test_the_manifest_uses_the_same_name_and_description(): void
    {
        $manifest = $this->get('/manifest.webmanifest')->assertOk()->json();

        $this->assertSame(config('app.name'), $manifest['name']);
        $this->assertSame(config('brand.description'), $manifest['description']);
    }

    public function test_the_manifest_does_not_lock_orientation(): void
    {
        // WCAG 1.3.4: pendaki memasang ponsel melintang di stang atau tas (F12, DK-19).
        $this->assertSame('any', $this->get('/manifest.webmanifest')->json('orientation'));
    }

    public function test_the_framework_logo_is_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/components/application-logo.blade.php'));

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $this->assertStringNotContainsString('x-application-logo', $berkas->getContents(), $berkas->getRelativePathname());
        }
    }

    public function test_the_mark_is_a_trail_not_a_mountain(): void
    {
        // R-001/R-002: Trail adalah unit keputusan, jadi tanda merek menggambar jalur
        // berpos, bukan siluet gunung. Hanya path dan cincin, tanpa segitiga.
        $tanda = File::get(resource_path('js/components/brand/Mark.tsx'));

        $this->assertSame(2, substr_count($tanda, '<circle'), 'Dua master, masing-masing dengan cincin pos.');
        $this->assertStringNotContainsString('<polygon', $tanda);
        $this->assertStringContainsString('stroke="currentColor"', $tanda, 'Satu berkas untuk tema terang, gelap, dan mono.');
        $this->assertStringContainsString('fill="none"', $tanda, 'Tanda adalah garis, bukan bidang.');

        // Lockup di setiap layout memakai tanda yang sama, bukan salinan gambarnya.
        $this->assertStringContainsString("import { Mark } from './Mark';", File::get(resource_path('js/components/brand/Lockup.tsx')));
    }

    public function test_the_public_copy_frames_readiness_as_decision_support(): void
    {
        // R-014 dan K32: "apakah sudah layak berangkat" terbaca sebagai izin.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('layak berangkat', $html);
        $this->assertStringContainsString(config('brand.tagline'), $html);
    }

    public function test_the_theme_colours_in_config_match_the_css_tokens(): void
    {
        $css = File::get(resource_path('css/app.css'));

        preg_match('/--brand-700:\s*(\d+)\s+(\d+)\s+(\d+);/', $css, $terang);
        preg_match("/:root\[data-theme='dark'\]\s*\{.*?--canvas:\s*(\d+)\s+(\d+)\s+(\d+);/s", $css, $gelap);

        $this->assertSame(config('brand.theme_color.light'), sprintf('#%02x%02x%02x', $terang[1], $terang[2], $terang[3]));
        $this->assertSame(config('brand.theme_color.dark'), sprintf('#%02x%02x%02x', $gelap[1], $gelap[2], $gelap[3]));
    }

    /**
     * Favikon mengikuti token merek v3 (rencana W1 tugas 1). Garisnya ditulis sebagai
     * heksa di SVG karena favikon dimuat di luar CSS aplikasi; test ini menjaga kedua
     * warnanya sama dengan isian merek terang dan merek latar depan gelap.
     */
    public function test_the_favicon_follows_the_brand_tokens(): void
    {
        $svg = File::get(public_path('favicon.svg'));
        $css = File::get(resource_path('css/app.css'));

        preg_match("/:root\[data-theme='dark'\]\s*\{.*?--brand-ink:\s*(\d+)\s+(\d+)\s+(\d+);/s", $css, $gelap);

        $this->assertCount(4, $gelap, 'Token brand-ink gelap tidak ditemukan.');
        $this->assertStringContainsString('stroke="'.config('brand.theme_color.light').'"', $svg);
        $this->assertStringContainsString(sprintf('stroke: #%02x%02x%02x;', $gelap[1], $gelap[2], $gelap[3]), $svg);
    }

    public function test_the_icons_and_favicons_referenced_by_the_head_exist(): void
    {
        foreach (['icons/app-192.png', 'icons/app-512.png', 'icons/app-512-maskable.png', 'icons/app-180.png', 'favicon.svg', 'favicon.ico'] as $berkas) {
            $this->assertFileExists(public_path($berkas));
        }
    }
}
