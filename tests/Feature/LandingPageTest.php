<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pintu depan produk masih halaman bawaan Laravel: pengunjung yang datang ke platform
 * pendakian disambut tautan dokumentasi Laravel dan Laracasts. Halaman itu juga yang
 * dituju setelah pengguna keluar.
 *
 * Kriteria clarity dan relevan keduanya gagal di halaman yang paling banyak dilihat
 * orang asing.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_learns_what_the_product_does(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('pendakian', strtolower($isi));
        $this->assertStringNotContainsString('Laracasts', $isi);
        $this->assertStringNotContainsString('laravel.com', $isi);
    }

    public function test_the_page_has_exactly_one_first_level_heading(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($isi, '<h1'), 'Satu h1 per halaman agar strukturnya terbaca pembaca layar.');
    }

    /**
     * CSP menolak gambar dari luar asal sendiri. Halaman bawaan Laravel memuat latar dari
     * laravel.com, sehingga kini rusak tanpa pesan apa pun.
     */
    public function test_nothing_is_loaded_from_an_outside_host(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/', $isi, $cocok);

        $luar = array_filter(
            $cocok[1],
            fn (string $url) => ! str_contains($url, 'localhost')
                && ! str_contains($url, '127.0.0.1')
                && ! str_contains($url, 'fonts.bunny.net')
        );

        $this->assertSame([], array_values($luar), 'Aset dari luar akan diblokir CSP.');
    }

    public function test_a_guest_is_offered_a_way_in(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee(route('register'), escape: false)
            ->assertSee(route('login'), escape: false);
    }

    /**
     * Pengguna yang sudah masuk tidak perlu ditawari mendaftar lagi; ia butuh jalan
     * kembali ke pekerjaannya.
     */
    public function test_a_signed_in_user_is_pointed_at_their_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee(route('dashboard'), escape: false)
            ->assertDontSee(route('register'), escape: false);
    }

    /**
     * Klaim yang tidak dapat dibuktikan adalah kebohongan pada produk keselamatan. Sumber
     * data harus disebut apa adanya, dan §44 melarang menyebutnya cuaca puncak.
     */
    public function test_the_page_names_its_data_sources_without_overclaiming(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('BMKG', $isi);
        $this->assertStringNotContainsString('cuaca puncak', strtolower($isi));
    }
}
