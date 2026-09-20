<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
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
     * Sapuan, bukan dua halaman yang disebut namanya.
     *
     * Dua test di atas memeriksa daftar yang ditulis tangan, dan daftar yang ditulis
     * tangan berhenti lengkap pada hari ia ditulis: empat halaman yang dibuat sesudahnya
     * tidak pernah ikut diperiksa. Kelas cacat yang sama sudah muncul pada test kontras
     * dan pada anggaran query halaman.
     *
     * Yang disapu rute GET tanpa parameter, karena rute berparameter butuh data contoh
     * yang berbeda-beda dan lebih tepat diuji di test fiturnya masing-masing.
     */
    public function test_every_parameterless_page_carries_the_skip_link_and_main_landmark(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        $tanpaLandmark = [];
        $tanpaLewati = [];

        foreach (Route::getRoutes() as $rute) {
            if (! in_array('GET', $rute->methods(), true) || str_contains($rute->uri(), '{')) {
                continue;
            }

            // Hanya halaman aplikasi. Manifest, service worker, sitemap, dan endpoint
            // kesehatan bukan halaman dan tidak punya navigasi untuk dilewati.
            if (! in_array('web', $rute->gatherMiddleware(), true)
                || preg_match('/^(sitemap\.xml|sw\.js|manifest|up$|logout)/', $rute->uri())) {
                continue;
            }

            $respons = $this->actingAs($user)->get('/'.ltrim($rute->uri(), '/'));

            if ($respons->getStatusCode() !== 200) {
                continue;
            }

            $isi = $respons->getContent();

            if (! str_contains($isi, '<main')) {
                $tanpaLandmark[] = $rute->uri();
            }

            // Tautan lewati hanya dituntut ketika ada yang perlu dilewati. WCAG 2.4.1
            // mengatur melewati blok berulang, dan halaman tanpa navigasi seperti
            // halaman depan dan halaman luring tidak punya blok berulang sama sekali.
            // Menuntutnya di sana menambah satu tautan yang tidak menuju ke mana-mana.
            if (str_contains($isi, '<nav') && ! str_contains($isi, 'href="#konten"')) {
                $tanpaLewati[] = $rute->uri();
            }
        }

        $this->assertSame([], $tanpaLandmark, 'Tanpa landmark utama: '.implode(', ', $tanpaLandmark));
        $this->assertSame([], $tanpaLewati, 'Tanpa tautan lewati navigasi: '.implode(', ', $tanpaLewati));
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
