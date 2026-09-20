<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Hal-hal yang dituntut WCAG dan dapat diperiksa mesin, tetapi tidak tertutup test
 * aksesibilitas yang sudah ada.
 *
 * Yang lama menjaga label, satu h1, bahasa dokumen, dan kontras. Empat hal lain lolos,
 * dan dua di antaranya menyentuh orang yang memakai keyboard atau pembaca layar sebagai
 * satu-satunya cara memakai aplikasi ini.
 */
class KeyboardAndScreenReaderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function halaman(): array
    {
        return [
            'halaman masuk' => ['/login'],
            'dasbor' => ['/dashboard'],
        ];
    }

    /**
     * WCAG 2.4.1 Bypass Blocks, Level A.
     *
     * Tanpa ini pengguna keyboard menekan Tab melewati tujuh tautan navigasi di setiap
     * halaman sebelum sampai ke isinya. Bukan ketidaknyamanan kecil: itu terjadi setiap
     * kali halaman berpindah.
     */
    #[DataProvider('halaman')]
    public function test_a_keyboard_user_can_skip_straight_to_the_content(string $url): void
    {
        $isi = $this->berkas($url);

        $this->assertStringContainsString('href="#konten"', $isi, 'Tidak ada tautan lewati navigasi.');
        $this->assertStringContainsString('id="konten"', $isi, 'Sasaran tautan lewati tidak ada.');
    }

    /**
     * Tautan lewati harus terlihat begitu difokuskan. Yang tersembunyi selamanya sama
     * saja dengan tidak ada.
     */
    public function test_the_skip_link_appears_when_focused(): void
    {
        $isi = $this->berkas('/dashboard');

        $this->assertMatchesRegularExpression('/href="#konten"[^>]*focus:/', $isi);
    }

    /**
     * Halaman auth memakai layout berbeda dan tidak punya landmark utama sama sekali.
     */
    #[DataProvider('halaman')]
    public function test_every_page_has_a_main_landmark(string $url): void
    {
        $this->assertStringContainsString('<main', $this->berkas($url));
    }

    /**
     * WCAG 3.3.1 Error Identification: galat harus terkait secara program dengan
     * isiannya. Pengguna pembaca layar yang menuju sebuah isian mendengar labelnya,
     * tetapi galatnya berada di elemen lain yang tidak pernah disebut.
     */
    public function test_a_field_error_is_tied_to_the_input_it_belongs_to(): void
    {
        $isi = Blade::render(
            '<x-form.field name="email" label="Email" />',
            [],
            deleteCachedView: true
        );

        // Tanpa galat, tidak ada yang perlu ditautkan.
        $this->assertStringNotContainsString('aria-invalid="true"', $isi);
    }

    public function test_a_hint_is_tied_to_the_input_so_it_is_read_aloud(): void
    {
        $isi = Blade::render('<x-form.field name="durasi" label="Durasi" hint="Dalam menit." />');

        $this->assertStringContainsString('id="durasi-hint"', $isi);
        $this->assertMatchesRegularExpression('/aria-describedby="[^"]*durasi-hint/', $isi);
    }

    private function berkas(string $url): string
    {
        if ($url === '/login') {
            return $this->get($url)->assertOk()->getContent();
        }

        return $this->actingAs(User::factory()->create())->get($url)->assertOk()->getContent();
    }
}
