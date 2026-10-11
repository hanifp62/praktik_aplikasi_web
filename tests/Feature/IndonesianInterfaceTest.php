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
        // Label diterjemahkan dari "Elevation gain (m)" menjadi "Tanjakan (m)" (ruling
        // audit-fix-B BF-6, M-3 sisa): nama kolom dieja dalam kata, dan katanya sendiri
        // tidak lagi berbahasa Inggris.
        $this->assertSame('Tanjakan (m) harus berupa bilangan bulat.', $pesan);
    }

    /**
     * Ruling audit-fix-B BF-7d: booking_closes_days_before tidak punya entri attributes
     * sama sekali (ditemukan ronde BF-6), sehingga pesan galatnya jatuh ke nama kolom
     * mentah. Istilahnya disamakan dengan label formulir nyatanya,
     * `resources/js/pages/Admin/Permits.tsx` ("Pemesanan ditutup H-") dan pesan `.lte`
     * yang sudah ada di `PermitRequirementRequest::messages()` ("Penutupan pemesanan
     * tidak boleh lebih awal daripada pembukaannya."), bukan istilah baru.
     */
    public function test_the_booking_closing_window_attribute_matches_its_real_form_label(): void
    {
        $pesan = Validator::make(
            ['booking_closes_days_before' => 'bukan angka'],
            ['booking_closes_days_before' => ['integer']]
        )->errors()->first('booking_closes_days_before');

        $this->assertStringNotContainsString('booking_closes_days_before', $pesan);
        $this->assertSame('Penutupan pemesanan (hari sebelum) harus berupa bilangan bulat.', $pesan);
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
     * Sapuan BF-7b (ruling audit-fix-B, M-3 sisa): "Elevation gain"/"Elevation loss" tidak
     * boleh muncul lagi sebagai PROSA bertampil (dengan spasi), di app/, resources/js,
     * resources/views, atau nilai lang/id/*.php. Identifier kode bergaris bawah
     * (elevation_gain_m dan seterusnya) sengaja TIDAK disentuh pola ini, karena polanya
     * menuntut spasi literal antara "elevation" dan "gain"/"loss".
     *
     * Komentar dan docblock yang memang tidak pernah dirender ke pengguna dilucuti dulu
     * (`/* ... *\/`, `{{-- ... --}}`, dan `//` yang tidak didahului titik dua supaya
     * "https://" tidak ikut terpotong), supaya sapuan ini hanya menjaga teks yang
     * sungguh tampil, bukan menolak penjelasan teknis berbahasa Inggris di balik kode.
     * Tanpa pengecualian: "Total elevation gain" di HikerProgress.tsx, yang sempat
     * dikecualikan selama Kelompok A bekerja di berkas itu, kini "Total tanjakan".
     */
    public function test_the_english_elevation_phrase_does_not_appear_in_displayed_text(): void
    {
        $pola = '/elevation[ \t]+(?:gain|loss)/i';
        $pelanggar = [];

        foreach ([app_path(), resource_path('js'), resource_path('views'), lang_path('id')] as $akar) {
            if (! is_dir($akar)) {
                continue;
            }

            foreach (File::allFiles($akar) as $berkas) {
                $namaBerkas = $berkas->getFilename();
                $isBlade = str_ends_with($namaBerkas, '.blade.php');

                if (! $isBlade && ! in_array($berkas->getExtension(), ['php', 'tsx', 'ts'], true)) {
                    continue;
                }

                $jalurUnix = str_replace('\\', '/', $berkas->getRelativePathname());

                $isi = $berkas->getContents();

                if ($isBlade) {
                    $isi = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $isi);
                }

                $isi = (string) preg_replace('#/\*.*?\*/#s', '', $isi);
                $isi = (string) preg_replace('#(?<!:)//[^\n]*#', '', $isi);

                if (preg_match($pola, $isi)) {
                    $pelanggar[] = $jalurUnix;
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Prosa "elevation gain/loss" masih tampil ke pengguna di: '.implode(', ', $pelanggar)
        );
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
