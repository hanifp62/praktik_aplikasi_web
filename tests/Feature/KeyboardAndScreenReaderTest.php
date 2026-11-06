<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
    use SumberReact;

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

            // Halaman React (Inertia) merender landmark utamanya di klien; yang dapat
            // diperiksa di server adalah tautan lewati di templat akar dan bahwa
            // layout React memasang id="konten". InertiaLayoutTest menjaga yang kedua.
            if (str_contains($isi, 'data-page="app"')) {
                if (! str_contains($isi, 'href="#konten"')) {
                    $tanpaLewati[] = $rute->uri();
                }

                continue;
            }

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
     *
     * Isian bersama React (FormField) menautkan galatnya lewat aria-describedby, dan
     * aria-invalid hanya dipasang ketika memang ada galat.
     */
    public function test_a_field_error_is_tied_to_the_input_it_belongs_to(): void
    {
        $isian = $this->formField();

        $this->assertStringContainsString('error ? `${name}-error` : null', $isian);
        $this->assertStringContainsString('<InputError id={`${name}-error`}', $isian);
        $this->assertStringContainsString('aria-describedby={penjelas}', $isian);
        $this->assertStringContainsString('aria-invalid={error ? true : undefined}', $isian);
    }

    public function test_a_hint_is_tied_to_the_input_so_it_is_read_aloud(): void
    {
        $isian = $this->formField();

        $this->assertStringContainsString('hint ? `${name}-hint` : null', $isian);
        $this->assertStringContainsString('<p id={`${name}-hint`}', $isian);
    }

    /**
     * Sapuan, bukan satu komponen. Isian yang dirakit sendiri di halaman admin lolos
     * dari FormField, dan di situlah galat yang tidak disebut ke pembaca layar pernah
     * bersembunyi: lima galat di Studi Kegunaan dan Kelola Jalur tidak punya id sama
     * sekali, sehingga tidak ada isian yang dapat menunjuknya.
     *
     * Setiap galat membawa id, dan id itu disebut lagi di luar elemennya sendiri, yaitu
     * di aria-describedby isiannya atau di variabel yang mengisinya. Petunjuk diperiksa
     * dengan cara yang sama.
     */
    public function test_every_error_and_hint_in_the_react_ui_is_referenced_by_its_control(): void
    {
        $pelanggar = [];
        $diperiksa = 0;

        foreach ($this->berkasTsx() as $berkas) {
            $isi = $berkas->getContents();
            $perKunci = [];

            foreach ($this->tagBernama($isi, 'InputError') as [$tag, $baris]) {
                $diperiksa++;

                if (! preg_match('/\bid=(?:"([^"]+)"|\{`([^`]+)`\})/', $tag, $id)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.$baris.' galat tanpa id';

                    continue;
                }

                $kunci = $id[1] !== '' ? $id[1] : $id[2];
                $perKunci[$kunci] = ($perKunci[$kunci] ?? 0) + 1;
            }

            preg_match_all('/\bid=(?:"([^"]+-hint)"|\{`([^`]+-hint)`\})/', $isi, $petunjuk, PREG_SET_ORDER);

            foreach ($petunjuk as $satu) {
                $diperiksa++;
                $kunci = $satu[1] !== '' ? $satu[1] : $satu[2];
                $perKunci[$kunci] = ($perKunci[$kunci] ?? 0) + 1;
            }

            foreach ($perKunci as $kunci => $jumlah) {
                if (substr_count($isi, $kunci) < 2 * $jumlah) {
                    $pelanggar[] = $berkas->getRelativePathname().': '.$kunci.' tidak ditunjuk isiannya';
                }
            }
        }

        // Penjaga terhadap sapuan yang hijau karena tidak menemukan apa pun.
        $this->assertGreaterThan(20, $diperiksa, 'Sapuan tidak menemukan galat maupun petunjuk untuk diperiksa.');
        $this->assertSame([], $pelanggar, "Galat atau petunjuk yang tidak terikat ke isiannya:\n".implode("\n", $pelanggar));
    }

    /**
     * Badan FormField saja, supaya penegasan di atas tidak lolos karena kalimat yang
     * sama kebetulan ada di komponen lain dalam berkas yang sama.
     */
    private function formField(): string
    {
        $isi = File::get(resource_path('js/components/ui/Field.tsx'));
        $mulai = strpos($isi, 'export function FormField(');

        $this->assertNotFalse($mulai, 'FormField tidak ditemukan di Field.tsx.');

        $akhir = strpos($isi, "\nexport ", $mulai + 1);

        return substr($isi, $mulai, $akhir === false ? null : $akhir - $mulai);
    }

    /**
     * Isi yang sampai ke pengguna pada halaman itu. Halaman React (Inertia) merender
     * landmark dan navigasinya di klien, jadi yang digabung adalah respons server
     * (templat akar dengan tautan lewati), sumber halamannya, dan sumber layout yang
     * dipakai halaman itu.
     */
    private function berkas(string $url): string
    {
        $respons = $url === '/login'
            ? $this->get($url)->assertOk()
            : $this->actingAs(User::factory()->create())->get($url)->assertOk();

        $isi = $respons->getContent();

        if (! str_contains($isi, 'data-page="app"')) {
            return $isi;
        }

        $halaman = File::get(resource_path('js/pages/'.$respons->viewData('page')['component'].'.tsx'));

        foreach (['AppLayout', 'GuestLayout'] as $layout) {
            if (str_contains($halaman, '<'.$layout.'>')) {
                $isi .= File::get(resource_path('js/layouts/'.$layout.'.tsx'));
            }
        }

        return $isi.$halaman;
    }
}
