<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Volt\Volt;
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
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function halamanTamu(): array
    {
        return [
            'login' => ['/login', 'Masuk', 'Log in'],
            'register' => ['/register', 'Daftar', 'Register'],
            'lupa kata sandi' => ['/forgot-password', 'Kirim Tautan Atur Ulang Kata Sandi', 'Email Password Reset Link'],
        ];
    }

    #[DataProvider('halamanTamu')]
    public function test_a_guest_page_speaks_indonesian(string $url, string $indonesia, string $inggris): void
    {
        $isi = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString($indonesia, $isi);
        $this->assertStringNotContainsString(
            '>'.$inggris.'<',
            $isi,
            "Halaman {$url} masih memakai teks bawaan Breeze berbahasa Inggris."
        );
    }

    public function test_the_profile_page_speaks_indonesian(): void
    {
        $isi = $this->actingAs(User::factory()->create())->get('/profile')->assertOk()->getContent();

        foreach (['Informasi Profil', 'Perbarui Kata Sandi', 'Hapus Akun'] as $teks) {
            $this->assertStringContainsString($teks, $isi);
        }
    }

    public function test_the_navigation_offers_indonesian_labels(): void
    {
        $isi = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Dasbor', $isi);
        $this->assertStringContainsString('Keluar', $isi);
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

        Volt::test('pages.auth.login')
            ->set('form.email', 'pendaki@contoh.id')
            ->set('form.password', 'salah-sekali')
            ->call('login')
            ->assertHasErrors('form.email');

        $this->assertSame('Email atau kata sandi tidak cocok.', trans('auth.failed'));
    }

    /**
     * Akun yang dihapus tidak benar-benar menghapus segalanya: laporan kondisi jalur
     * yang sudah disetujui tetap disimpan tanpa nama pemiliknya, sehingga janji
     * "semua data dihapus permanen" pada salinan bawaan Breeze tidak jujur.
     */
    public function test_the_account_deletion_copy_matches_what_actually_happens(): void
    {
        $isi = $this->actingAs(User::factory()->create())->get('/profile')->assertOk()->getContent();

        $this->assertStringContainsString('tetap disimpan tanpa nama Anda', $isi);
    }
}
