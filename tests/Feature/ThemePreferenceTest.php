<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Tema tampilan dirender server dari cookie (DK-17).
 *
 * Livewire wire:navigate mengganti atribut <html> pada setiap perpindahan halaman,
 * jadi tema yang hanya disetel JavaScript terlempar kembali ke terang di tengah sesi.
 * Yang dikunci di sini: atribut dan meta datang dari server, nilai cookie diwhitelist,
 * dan cookie-nya tidak dienkripsi supaya skrip pemulih riwayat dapat membacanya.
 */
class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_cookie_the_page_follows_the_system_scheme(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-theme=', $html);
        $this->assertStringContainsString('<meta name="color-scheme" content="light dark">', $html);
        $this->assertStringContainsString('media="(prefers-color-scheme: light)"', $html);
        $this->assertStringContainsString('media="(prefers-color-scheme: dark)"', $html);
    }

    public function test_a_dark_cookie_is_rendered_by_the_server_before_any_script_runs(): void
    {
        $html = $this->withUnencryptedCookie(ThemePreference::COOKIE, 'dark')->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-theme="dark"', $html);
        $this->assertStringContainsString('<meta name="color-scheme" content="dark">', $html);
        $this->assertStringContainsString('<meta name="theme-color" content="'.config('brand.theme_color.dark').'">', $html);
        $this->assertStringNotContainsString('media="(prefers-color-scheme', $html);
    }

    public function test_a_light_cookie_overrides_a_dark_system(): void
    {
        $html = $this->withUnencryptedCookie(ThemePreference::COOKIE, 'light')->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-theme="light"', $html);
        $this->assertStringContainsString('<meta name="theme-color" content="'.config('brand.theme_color.light').'">', $html);
    }

    /**
     * Cookie datang dari peramban, dan peramban milik siapa saja. Nilai di luar daftar
     * putih tidak boleh sampai ke atribut HTML, apalagi tanpa disaring.
     */
    public function test_a_forged_cookie_value_never_reaches_the_markup(): void
    {
        $html = $this->withUnencryptedCookie(ThemePreference::COOKIE, '"><script>alert(1)</script>')
            ->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-theme=', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('content="light dark"', $html);
    }

    public function test_choosing_dark_sets_a_readable_cookie_and_returns_to_the_page(): void
    {
        $respons = $this->from('/')->post(route('tema.simpan'), ['tema' => 'dark']);

        $respons->assertRedirect('/');

        $cookie = collect($respons->headers->getCookies())->first(fn ($c) => $c->getName() === ThemePreference::COOKIE);

        $this->assertNotNull($cookie);
        $this->assertSame('dark', $cookie->getValue(), 'Cookie tema harus tersimpan apa adanya, bukan dienkripsi.');
        $this->assertFalse($cookie->isHttpOnly(), 'Skrip pemulih riwayat perlu membacanya.');
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_choosing_system_forgets_the_cookie(): void
    {
        $respons = $this->withUnencryptedCookie(ThemePreference::COOKIE, 'dark')
            ->from('/')->post(route('tema.simpan'), ['tema' => 'system']);

        $cookie = collect($respons->headers->getCookies())->first(fn ($c) => $c->getName() === ThemePreference::COOKIE);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isCleared(), 'Ikuti sistem berarti tidak ada cookie, bukan cookie bernilai "system".');
    }

    public function test_an_unknown_choice_is_rejected(): void
    {
        $this->from('/')->post(route('tema.simpan'), ['tema' => 'sepia'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('tema');
    }

    public function test_the_picker_marks_the_active_choice(): void
    {
        // Halaman depan dan Profil kini React: pemilih membaca pilihan aktif dari prop
        // bersama `tema`, jadi yang dijaga adalah prop itu di kedua halaman.
        $this->withUnencryptedCookie(ThemePreference::COOKIE, 'dark')
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Welcome')->where('tema', 'dark'));

        $user = User::factory()->create();
        $this->withUnencryptedCookie(ThemePreference::COOKIE, 'dark')->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Profile/Edit')->where('tema', 'dark'));
    }

    /**
     * Kunjungan Inertia mengganti komponen halaman, bukan <html>, jadi data-theme dari
     * server tertinggal sesudah memilih tema tanpa muat penuh. Pemilih tema dulu memicu
     * peristiwa di window sementara pendengarnya di document, sehingga tema baru baru
     * terlihat setelah halaman dimuat ulang (dibuktikan di peramban, 25 September 2026).
     */
    public function test_the_react_app_reapplies_the_theme_from_the_cookie_on_every_visit(): void
    {
        $app = File::get(resource_path('js/app.tsx'));
        $dasar = File::get(resource_path('js/dasar.js'));

        $this->assertMatchesRegularExpression("/router[.]on[(]'navigate',[^;]*terapkanTema/", $app);
        $this->assertStringContainsString('window.terapkanTema = terapkanTema', $dasar);

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $this->assertStringNotContainsString('livewire:navigated', $berkas->getContents(), $berkas->getRelativePathname());
        }
    }

    /**
     * Ruling AF-1 (audit E2E I-1): POST /tema kembali ke URL yang sama, dan Inertia 3.7.1 tidak
     * memancarkan navigate untuk kunjungan replace ke URL yang sama, jadi pendengar di app.tsx
     * tidak pernah berjalan dan tema baru baru terlihat sesudah pindah halaman. Pemilih tema
     * karena itu menerapkan cookie baru sendiri begitu kirimannya berhasil, di Profil dan di kaki
     * beranda tamu yang memakai komponen yang sama.
     */
    public function test_the_picker_applies_the_new_theme_as_soon_as_the_server_accepts_it(): void
    {
        $pemilih = File::get(resource_path('js/components/ui/ThemePicker.tsx'));

        $this->assertMatchesRegularExpression(
            '/router\.post\(simpan\.url\(\), \{ tema: nilai \}, \{[^}]*onSuccess: \(\) => window\.terapkanTema\(\),/s',
            $pemilih,
        );
        $this->assertStringContainsString('<ThemePicker />', File::get(resource_path('js/pages/Profile/Edit.tsx')));
        $this->assertStringContainsString('<ThemePicker compact />', File::get(resource_path('js/pages/Welcome.tsx')));
    }
}
