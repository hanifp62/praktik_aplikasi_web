<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Meta sosial.
 *
 * Halaman jalur publik dibuat khusus untuk ditemukan dan dibagikan, lalu setiap
 * tautannya muncul sebagai URL telanjang tanpa judul maupun gambar. Nol og:, nol
 * twitter:, dan tidak ada rel="icon" di mana pun meskipun berkasnya sudah ada di
 * public/ sejak awal — cacat pertumbuhan yang diperkenalkan bersama halamannya sendiri.
 */
class SocialMetaTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->easy()->for(
            Mountain::factory()->create(['name' => 'Merbabu', 'province' => 'Jawa Tengah'])
        )->create(['name' => 'Jalur Selo', 'is_published' => true, 'archived_at' => null]);
    }

    public function test_a_public_trail_page_carries_its_own_social_card(): void
    {
        $halaman = $this->get(route('public.trail', $this->jalur()));

        foreach (['og:title', 'og:description', 'og:url', 'og:image', 'twitter:card'] as $tag) {
            $halaman->assertSee($tag, escape: false);
        }
    }

    /**
     * Judul sosialnya menyebut jalur dan gunungnya, bukan nama aplikasi berulang-ulang.
     * Tautan yang ditempel di grup pendakian harus memberi tahu jalur apa itu sebelum
     * ada yang mengetuknya.
     */
    public function test_the_social_title_names_the_trail_not_the_app(): void
    {
        $this->get(route('public.trail', $this->jalur()))
            ->assertSee('content="Jalur Selo', escape: false);
    }

    /**
     * Gambar sosialnya ikon aplikasi, bukan foto jalur.
     *
     * Foto jalur berasal dari laporan komunitas yang membawa nama pelapornya, dan
     * halaman publik tidak pernah menerbitkan data pribadi siapa pun.
     */
    public function test_the_social_image_is_not_community_content(): void
    {
        $this->get(route('public.trail', $this->jalur()))
            ->assertSee('/icons/app-512.png', escape: false);
    }

    public function test_every_layout_declares_the_site_icon(): void
    {
        foreach (['layouts/app', 'layouts/guest', 'public/trail'] as $berkas) {
            $this->assertStringContainsString(
                'rel="icon"',
                File::get(resource_path("views/{$berkas}.blade.php")),
                "{$berkas} tidak menyatakan ikon situs."
            );
        }
    }

    /**
     * Berkas yang ditunjuk benar-benar ada. Tag yang menunjuk berkas hilang gagal
     * diam-diam: peramban tidak mengeluh, ikonnya sekadar tidak muncul.
     */
    public function test_the_files_the_tags_point_at_exist(): void
    {
        foreach (['favicon.ico', 'icons/app-192.png', 'icons/app-512.png'] as $berkas) {
            $this->assertFileExists(public_path($berkas));
        }
    }
}
