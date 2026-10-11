<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pintu depan produk. Dulu halaman bawaan Laravel dengan tautan Laracasts; kini
 * halaman React pertama (jalur C). Yang dirender server: judul, meta description, dan
 * props; isinya sendiri diperiksa dari sumber komponen karena dirender di klien.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_learns_what_the_product_does(): void
    {
        $respons = $this->get('/')->assertOk();
        $isi = $respons->getContent();

        $this->assertStringContainsString('pendaki', strtolower($isi));
        $this->assertStringNotContainsString('Laracasts', $isi);
        $this->assertStringNotContainsString('laravel.com', $isi);

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->where('title', config('app.name').': '.config('brand.tagline'))
            ->where('description', config('brand.description')));

        $this->assertStringContainsString('<title>'.config('app.name').': '.config('brand.tagline').'</title>', $isi);
        $this->assertStringContainsString('<meta name="description" content="'.config('brand.description').'">', $isi);
    }

    public function test_the_page_has_exactly_one_first_level_heading(): void
    {
        $this->assertSame(1, substr_count($this->sumber(), '<h1'), 'Satu h1 per halaman agar strukturnya terbaca pembaca layar.');
    }

    /**
     * CSP menolak aset dari luar asal sendiri.
     *
     * Diperbarui gelombang 1 v3: pengecualian Bunny Fonts dicabut karena huruf kini
     * dihosting sendiri (DK-38), jadi halaman depan tidak memuat apa pun dari luar.
     */
    public function test_nothing_is_loaded_from_an_outside_host(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/', $isi, $cocok);

        $luar = array_filter(
            $cocok[1],
            fn (string $url) => ! str_contains($url, 'localhost')
                && ! str_contains($url, '127.0.0.1')
        );

        $this->assertSame([], array_values($luar), 'Aset dari luar akan diblokir CSP.');
    }

    public function test_a_guest_is_offered_a_way_in(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user', null));

        $sumber = $this->sumber();

        $this->assertStringContainsString('register.url()', $sumber);
        $this->assertStringContainsString('login.url()', $sumber);
        $this->assertStringContainsString('Masuk', $sumber);
    }

    /**
     * Pengguna yang sudah masuk tidak perlu ditawari mendaftar lagi; ia butuh jalan
     * kembali ke pekerjaannya. Komponen memilih cabangnya dari auth.user.
     */
    public function test_a_signed_in_user_is_pointed_at_their_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.id', $user->id));

        $this->assertMatchesRegularExpression('/auth\.user \? \(\s*<>\s*<Button href=\{dashboard\.url\(\)\}/s', $this->sumber());
    }

    /**
     * Klaim yang tidak dapat dibuktikan adalah kebohongan pada produk keselamatan. Sumber
     * data harus disebut apa adanya, dan §44 melarang menyebutnya cuaca puncak.
     */
    public function test_the_page_names_its_data_sources_without_overclaiming(): void
    {
        $sumber = $this->sumber();

        $this->assertStringContainsString('BMKG', $sumber);
        $this->assertStringNotContainsString('cuaca puncak', strtolower($sumber));
    }

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Welcome.tsx'));
    }
}
