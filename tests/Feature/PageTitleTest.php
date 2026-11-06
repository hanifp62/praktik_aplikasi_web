<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-10.
 *
 * Judul menentukan label tab, riwayat browser, dan bookmark. Cacat asalnya: layout lama
 * menulis config('app.name') alih-alih judul halaman, sehingga setiap halaman berjudul
 * sama. Kini setiap halaman Inertia mengirim prop `title` yang dirender server dan
 * dipasang ulang klien dengan format "Judul · Memount".
 */
class PageTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_page_uses_its_own_title(): void
    {
        $this->assertSame('Jelajahi Jalur · Memount', $this->titleOf(route('trails.index'), User::factory()->create()));
    }

    public function test_another_page_uses_a_different_title(): void
    {
        $this->assertSame('Trip Saya · Memount', $this->titleOf(route('trips.index'), User::factory()->create()));
    }

    public function test_a_plain_view_page_has_a_title_too(): void
    {
        $this->assertSame('Profil Akun · Memount', $this->titleOf(route('profile'), User::factory()->create()));
    }

    public function test_an_admin_page_has_its_own_title(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->assertSame('Kelola Gunung · Memount', $this->titleOf(route('admin.mountains'), $admin));
    }

    public function test_the_application_is_not_named_laravel(): void
    {
        $this->assertNotSame('Laravel', config('app.name'), 'Nama aplikasi masih bawaan framework.');
    }

    /**
     * Mengambil isi <title> saja; membandingkan seluruh HTML membuat kegagalan test
     * tidak terbaca.
     */
    private function titleOf(string $url, User $user): string
    {
        $html = $this->actingAs($user)->get($url)->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $matches);

        return trim($matches[1] ?? '');
    }
}
