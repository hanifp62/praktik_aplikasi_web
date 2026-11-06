<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman akun (masuk, daftar, kata sandi, verifikasi, profil) dirender klien, di luar
 * jangkauan AccessibilityTest yang memeriksa HTML server. Yang dijaga dari sumbernya:
 * tepat satu h1 per halaman, dan kotak centang yang terikat ke labelnya. Isian teks dan
 * kata sandi terikat lewat FormField dan IsianKataSandi, yang selalu memasang label.
 */
class AccountPagesSourceTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function halaman(): array
    {
        return [
            'masuk' => ['Auth/Login'],
            'daftar' => ['Auth/Register'],
            'lupa kata sandi' => ['Auth/ForgotPassword'],
            'atur ulang' => ['Auth/ResetPassword'],
            'verifikasi' => ['Auth/VerifyEmail'],
            'konfirmasi' => ['Auth/ConfirmPassword'],
            'profil' => ['Profile/Edit'],
        ];
    }

    #[DataProvider('halaman')]
    public function test_each_account_page_has_exactly_one_first_level_heading(string $komponen): void
    {
        $isi = File::get(resource_path("js/pages/{$komponen}.tsx"));

        $this->assertSame(1, substr_count($isi, '<PageHeader ') + substr_count($isi, '<h1'), "{$komponen} harus punya tepat satu h1.");
    }

    public function test_every_checkbox_on_the_account_pages_has_a_label(): void
    {
        $berkas = [
            ...File::files(resource_path('js/components/akun')),
            ...File::files(resource_path('js/pages/Auth')),
        ];

        $diperiksa = 0;

        foreach ($berkas as $b) {
            preg_match_all('/<Checkbox\s[^>]*id="([^"]+)"/s', $b->getContents(), $cocok);

            foreach ($cocok[1] as $id) {
                $diperiksa++;
                $this->assertStringContainsString('htmlFor="'.$id.'"', $b->getContents(), "Kotak centang #{$id} tanpa label.");
            }
        }

        $this->assertSame(2, $diperiksa, 'Ingat saya dan perekaman jejak.');
    }
}
