<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §106 menyebut PWA installability sebagai should-have. Untuk produk ini nilainya
 * lebih besar daripada yang tersirat di sana: aplikasinya dipakai di tempat bersinyal
 * tipis, dan shell yang tersimpan berarti halamannya terbuka di basecamp alih-alih
 * menggantung di layar putih.
 *
 * Yang tidak boleh terjadi: menyajikan data kondisi dari cache. Status resmi yang
 * kedaluwarsa dan disajikan seolah kini adalah pelanggaran §94 dan §95 sekaligus, dan
 * pada produk keselamatan itu jauh lebih berbahaya daripada halaman yang gagal terbuka.
 */
class ProgressiveWebAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_served(): void
    {
        $manifest = $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->json();

        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['name']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_the_manifest_starts_the_app_where_the_user_left_off(): void
    {
        $manifest = $this->get('/manifest.webmanifest')->assertOk()->json();

        $this->assertSame('/dashboard', $manifest['start_url']);
    }

    public function test_both_layouts_link_the_manifest(): void
    {
        foreach (['app', 'guest'] as $layout) {
            $isi = file_get_contents(resource_path('views/layouts/'.$layout.'.blade.php'));

            $this->assertStringContainsString('manifest.webmanifest', $isi, $layout.' belum menautkan manifest.');
        }
    }

    public function test_the_service_worker_is_served_from_the_root(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript');
    }

    /**
     * Inti kebijakannya. Shell boleh disimpan, isi tidak.
     */
    public function test_the_service_worker_never_caches_page_responses(): void
    {
        $sw = $this->get('/sw.js')->assertOk()->getContent();

        $this->assertStringContainsString("request.destination === 'document'", $sw);

        // Dokumen diambil dari jaringan lebih dulu, dan kegagalannya jatuh ke halaman
        // luring, bukan ke salinan halaman itu sendiri.
        $this->assertMatchesRegularExpression(
            "/fetch\(request\)\s*\.catch\(\(\)\s*=>\s*caches\.match\('\/offline'\)\)/",
            $sw
        );
        $this->assertStringNotContainsString('caches.match(request).then((tersimpan) => tersimpan || fetch(request)).catch', $sw);
    }

    public function test_the_offline_page_refuses_to_pretend(): void
    {
        $isi = $this->get('/offline')->assertOk()->getContent();

        $this->assertStringContainsString('luring', strtolower($isi));

        // Halaman luring tidak boleh menyebut kondisi apa pun sebagai fakta terkini.
        // Dicocokkan sebagai kata utuh: "aman" sebagai substring ikut kena pada "halaman".
        foreach (['BUKA', 'TUTUP', 'Cuaca saat ini', 'aman', 'siap berangkat'] as $klaim) {
            $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($klaim, '/').'\b/u', $isi);
        }
    }

    public function test_the_offline_page_does_not_depend_on_anything_that_may_be_broken(): void
    {
        $sumber = file_get_contents(resource_path('views/offline.blade.php'));

        foreach (['@livewire', 'auth()->user()', 'DB::'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $sumber);
        }
    }
}
