<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DisplayPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Preferensi Tampilan (Profil): Efek transparan dan Gerak.
 *
 * Safari dan Firefox belum meneruskan prefers-reduced-transparency, dan sebagian
 * pengguna tidak tahu letak setelan gerak di sistemnya (riset 2026-09-25-uiux/01 §5.2,
 * 03 §6.8). Pilihannya disimpan di cookie dan dirender server sebagai atribut <html>,
 * sama dengan tema, supaya halaman pertama sudah benar sebelum skrip berjalan.
 */
class DisplayPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_cookies_the_page_follows_the_system(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-efek=', $html);
        $this->assertStringNotContainsString('data-gerak=', $html);
    }

    public function test_the_choices_are_rendered_by_the_server_before_any_script_runs(): void
    {
        $html = $this->withUnencryptedCookie(DisplayPreference::COOKIE_EFEK, 'solid')
            ->withUnencryptedCookie(DisplayPreference::COOKIE_GERAK, 'kurangi')
            ->get('/')->getContent();

        $this->assertStringContainsString('data-efek="solid"', $html);
        $this->assertStringContainsString('data-gerak="kurangi"', $html);
    }

    public function test_a_forged_cookie_value_never_reaches_the_markup(): void
    {
        $html = $this->withUnencryptedCookie(DisplayPreference::COOKIE_EFEK, '"><script>alert(1)</script>')
            ->withUnencryptedCookie(DisplayPreference::COOKIE_GERAK, 'penuh')
            ->get('/')->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('data-efek=', $html);
        $this->assertStringNotContainsString('data-gerak=', $html);
    }

    public function test_choosing_solid_and_reduced_motion_sets_readable_cookies(): void
    {
        $respons = $this->from('/trails')->post(route('tampilan.simpan'), ['efek' => 'solid', 'gerak' => 'kurangi']);

        $respons->assertRedirect('/trails');
        $efek = collect($respons->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === DisplayPreference::COOKIE_EFEK);
        $gerak = collect($respons->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === DisplayPreference::COOKIE_GERAK);

        $this->assertSame('solid', $efek?->getValue());
        $this->assertSame('kurangi', $gerak?->getValue());
        $this->assertFalse($efek->isHttpOnly(), 'Skrip peramban perlu membacanya setelah kunjungan Inertia.');
    }

    public function test_choosing_the_defaults_forgets_the_cookies(): void
    {
        $respons = $this->post(route('tampilan.simpan'), ['efek' => 'otomatis', 'gerak' => 'sistem']);

        foreach ([DisplayPreference::COOKIE_EFEK, DisplayPreference::COOKIE_GERAK] as $nama) {
            $cookie = collect($respons->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === $nama);
            $this->assertNotNull($cookie);
            $this->assertTrue($cookie->getExpiresTime() < time(), "{$nama} harus dihapus, bukan disimpan.");
        }
    }

    public function test_an_unknown_choice_is_rejected(): void
    {
        $this->post(route('tampilan.simpan'), ['efek' => 'kaca-penuh'])->assertSessionHasErrors('efek');
        $this->post(route('tampilan.simpan'), [])->assertSessionHasErrors();
    }

    public function test_the_current_choices_are_shared_with_every_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->withUnencryptedCookie(DisplayPreference::COOKIE_EFEK, 'solid')
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('tampilan.efek', 'solid')
                ->where('tampilan.gerak', 'sistem'));
    }

    /**
     * Kunjungan Inertia tidak merender ulang <html>, jadi klien menyamakan atributnya
     * dengan cookie pada setiap navigasi, sama dengan tema.
     */
    public function test_the_client_reapplies_the_choices_on_every_visit(): void
    {
        $this->assertMatchesRegularExpression("/router[.]on[(]'navigate',[^;]*terapkanTampilan/s", File::get(resource_path('js/app.tsx')));
        $this->assertStringContainsString('window.terapkanTampilan = terapkanTampilan', File::get(resource_path('js/dasar.js')));
    }
}
