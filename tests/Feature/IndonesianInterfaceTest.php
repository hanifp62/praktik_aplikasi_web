<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Seluruh aplikasi ditulis dalam bahasa Indonesia, kecuali halaman auth dan profil
 * bawaan Breeze serta semua pesan validasi, yang tetap berbahasa Inggris karena
 * APP_LOCALE=id tidak punya berkas terjemahan untuk dituju.
 *
 * Akibatnya pendaki mendaftar dalam satu bahasa lalu memakai aplikasi dalam bahasa
 * lain, dan setiap formulir yang gagal menjawab dalam bahasa yang bukan bahasanya.
 */
class IndonesianInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_runs_in_indonesian(): void
    {
        $this->assertSame('id', config('app.locale'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function halamanTamu(): array
    {
        return [
            'login' => ['/login', 'Auth/Login', 'Masuk', 'Log in'],
            'register' => ['/register', 'Auth/Register', 'Daftar', 'Register'],
            'lupa kata sandi' => ['/forgot-password', 'Auth/ForgotPassword', 'Kirim Tautan Atur Ulang Kata Sandi', 'Email Password Reset Link'],
        ];
    }

    /**
     * Halaman tamu kini React: teksnya dirender klien, jadi yang diperiksa adalah bahwa
     * rute merender komponen itu dan sumber komponennya berbahasa Indonesia.
     */
    #[DataProvider('halamanTamu')]
    public function test_a_guest_page_speaks_indonesian(string $url, string $komponen, string $indonesia, string $inggris): void
    {
        $this->get($url)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component($komponen));

        $isi = File::get(resource_path("js/pages/{$komponen}.tsx"));

        $this->assertStringContainsString($indonesia, $isi);
        $this->assertStringNotContainsString(
            '>'.$inggris.'<',
            $isi,
            "Halaman {$url} masih memakai teks bawaan Breeze berbahasa Inggris."
        );
    }

    public function test_the_profile_page_speaks_indonesian(): void
    {
        $isi = $this->sumberProfil();

        foreach (['Informasi Profil', 'Perbarui Kata Sandi', 'Hapus Akun'] as $teks) {
            $this->assertStringContainsString($teks, $isi);
        }
    }

    public function test_the_navigation_offers_indonesian_labels(): void
    {
        $isi = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->getContent();

        // Judul dirender server dari prop halaman; navigasinya dirender layout React di
        // klien, jadi labelnya diperiksa dari sumber layout.
        $this->assertStringContainsString('Beranda', $isi);

        $layout = collect(File::files(resource_path('js/layouts')))->map(fn ($f) => $f->getContents())->implode("\n");
        $this->assertStringContainsString('Keluar', $layout);
    }

    /**
     * Menyisir setiap string __() di seluruh tampilan dan memastikan ada terjemahannya.
     *
     * Tanpa ini, satu halaman baru yang memakai kalimat Inggris akan lolos: string yang
     * tidak punya terjemahan dikembalikan Laravel apa adanya, tanpa galat.
     */
    public function test_every_translatable_string_has_an_indonesian_translation(): void
    {
        $belum = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            preg_match_all("/__\('([^']+)'\)/", $berkas->getContents(), $cocok);

            foreach ($cocok[1] as $string) {
                // Kunci berkas lang seperti auth.password diselesaikan lewat lang/id/.
                if (! str_contains($string, ' ') && str_contains($string, '.')) {
                    continue;
                }

                // Ditanyakan apakah kuncinya ada, bukan apakah hasilnya berbeda:
                // terjemahan "Email" memang "Email", dan itu bukan tanda terlewat.
                if (! Lang::has($string)) {
                    $belum[$string] = true;
                }
            }
        }

        $this->assertSame([], array_keys($belum), 'Belum diterjemahkan: '.implode(' | ', array_keys($belum)));
    }

    public function test_validation_messages_are_indonesian(): void
    {
        $pesan = Validator::make(['email' => ''], ['email' => ['required']])->errors()->first('email');

        $this->assertSame('Email wajib diisi.', $pesan);
    }

    public function test_validation_messages_name_the_field_in_words_not_column_names(): void
    {
        $pesan = Validator::make(
            ['elevation_gain_m' => 'bukan angka'],
            ['elevation_gain_m' => ['integer']]
        )->errors()->first('elevation_gain_m');

        $this->assertStringNotContainsString('elevation_gain_m', $pesan);
        $this->assertSame('Elevation gain (m) harus berupa bilangan bulat.', $pesan);
    }

    public function test_a_failed_login_answers_in_indonesian(): void
    {
        User::factory()->create(['email' => 'pendaki@contoh.id']);

        $this->post(route('login.store'), ['email' => 'pendaki@contoh.id', 'password' => 'salah-sekali'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak cocok.']);
    }

    /**
     * Akun yang dihapus tidak benar-benar menghapus segalanya: laporan kondisi jalur
     * yang sudah disetujui tetap disimpan tanpa nama pemiliknya, sehingga janji
     * "semua data dihapus permanen" pada salinan bawaan Breeze tidak jujur.
     */
    public function test_the_account_deletion_copy_matches_what_actually_happens(): void
    {
        $this->assertStringContainsString('tetap disimpan tanpa nama Anda', $this->sumberProfil());
    }

    /**
     * Halaman Profil kini React: rutenya harus merender komponen itu, dan teksnya
     * tinggal di sumber halaman beserta komponen bagian-bagiannya.
     */
    private function sumberProfil(): string
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Profile/Edit'));

        return collect([resource_path('js/pages/Profile/Edit.tsx'), ...File::files(resource_path('js/components/akun'))])
            ->map(fn ($berkas) => File::get((string) $berkas))
            ->implode("\n");
    }
}
