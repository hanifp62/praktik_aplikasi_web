<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Menu desktop dan menu ponsel harus menyajikan hal yang sama dalam urutan yang sama.
 *
 * Pada navigasi Blade lama keduanya ditulis terpisah di berkas yang sama, dan item
 * kedua dan ketiga sempat tertukar: pengguna yang berpindah dari ponsel ke laptop
 * menemukan menunya di tempat berbeda. Navigasi React menutup kelas cacat itu dengan
 * satu daftar, `permukaan` di NavLinks.tsx, yang digambar oleh tab atas dan navigasi
 * bawah ponsel. Isi daftarnya mengikuti arsitektur informasi v2 pemilik (DK-25,
 * 25 September 2026): Home, Explore, Trips, Track, Progress; Profil dan Community bukan tab.
 */
class NavigationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_menus_list_the_same_destinations_in_the_same_order(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertMatchesRegularExpression('/permukaan\.map\(\(p\) => \(\s*<TautanTab\b/', $layout, 'Tab desktop tidak lagi digambar dari daftar permukaan.');
        $this->assertMatchesRegularExpression('/permukaan\.map\(\(p\) => \(\s*<TabBawah\b/', $layout, 'Navigasi bawah ponsel tidak lagi digambar dari daftar permukaan.');
        $this->assertSame(2, substr_count($layout, 'permukaan.map('), 'Ada menu permukaan ketiga yang dirakit sendiri.');

        // Profil bukan tab; di ponsel ia tinggal di menu akun.
        $this->assertStringContainsString('<TautanResponsif href={profile.url()}>Profil</TautanResponsif>', $layout, 'Profil harus terjangkau dari menu ponsel.');
    }

    /**
     * Urutan tab adalah urutan pemilik: Beranda dulu karena ia pintu personal, lalu
     * menemukan, merencanakan, mendaki, dan melihat perkembangan diri.
     */
    public function test_the_order_follows_the_owners_information_architecture(): void
    {
        $isi = $this->daftarPermukaan();

        preg_match_all('/href: ([A-Za-z]+)\.url\(\)/', $isi, $tujuan);

        $this->assertSame(['dashboard', 'trailsIndex', 'tripsIndex', 'track', 'progress'], $tujuan[1]);

        // Nama lokal di atas harus menunjuk rute yang dimaksud, bukan sekadar bernama mirip.
        $navigasi = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $this->assertStringContainsString("import { dashboard, progress, track } from '@/routes';", $navigasi);
        $this->assertStringContainsString("import { index as trailsIndex } from '@/routes/trails';", $navigasi);
        $this->assertStringContainsString("import { index as tripsIndex } from '@/routes/trips';", $navigasi);
    }

    /**
     * Label berbahasa Indonesia untuk lima tab pemilik (spec 2026-09-25, asumsi A1).
     */
    public function test_the_react_navigation_lists_the_same_five_surfaces_in_order(): void
    {
        preg_match_all("/label: '([^']+)'/", $this->daftarPermukaan(), $cocok);

        $this->assertSame(['Beranda', 'Jelajah', 'Trip', 'Mendaki', 'Progres'], $cocok[1]);
    }

    /**
     * Banding milik tab Trip (TRIPS > Compare). Path-nya di bawah /trails, jadi tanpa
     * pengecualian Jelajah ikut menyala dan pembaca tidak tahu tab mana yang ia buka.
     */
    public function test_the_compare_page_lights_trip_not_jelajah(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.compare'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trails/Compare'));

        $this->assertStringContainsString("!p.startsWith('/trails/compare')", $this->aturan('Jelajah'), 'Jelajah ikut menyala di halaman banding.');
        $this->assertStringContainsString("p.startsWith('/trails/compare')", $this->aturan('Trip'), 'Tab Trip seharusnya menyala di halaman banding.');
    }

    /**
     * Daftar tersimpan milik Jelajah (EXPLORE > Saved), bukan tab Trip.
     */
    public function test_the_saved_list_belongs_to_jelajah(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.saved'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trails/Saved'));

        $this->assertStringNotContainsString('/trails/saved', $this->aturan('Jelajah'), 'Jelajah tidak boleh mengecualikan daftar tersimpan.');
        $this->assertStringNotContainsString('/trails/saved', $this->aturan('Trip'), 'Daftar tersimpan bukan bagian tab Trip.');
    }

    /**
     * Mode pendakian satu trip tinggal di /trips/{id}/hike, di bawah path tab Trip. Tanpa
     * pengecualian, dua tab menyala ketika pendaki sedang di jalur.
     */
    public function test_hike_mode_lights_mendaki_not_trip(): void
    {
        $this->assertStringContainsString('!modePendakian(p)', $this->aturan('Trip'));
        $this->assertStringContainsString('modePendakian(p)', $this->aturan('Mendaki'));
        $this->assertStringContainsString("p.startsWith('/mendaki')", $this->aturan('Mendaki'));
    }

    /**
     * Pendaki tidak pernah melihat pintu Admin maupun Moderasi.
     *
     * Menu profil React menampilkan keduanya hanya bila props bersama menyatakan
     * perannya, jadi yang dijaga adalah props yang dikirim server untuk pendaki biasa
     * dan syarat di layoutnya.
     */
    public function test_a_hiker_never_sees_the_admin_entry(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('admin.trails'), escape: false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.isAdmin', false)
                ->where('auth.user.isModerator', false));

        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        preg_match_all('/\{([^{}]*)\?\s*<Tautan(?:Menu|Responsif) href="\/admin\/trails">Admin</', $layout, $admin);
        preg_match_all('/\{([^{}]*)\?\s*<Tautan(?:Menu|Responsif) href="\/moderation">Moderasi</', $layout, $moderasi);

        $this->assertSame(['pengguna.isAdmin ', 'pengguna.isAdmin '], $admin[1], 'Pintu Admin harus dijaga peran di kedua menu.');
        $this->assertSame(['pengguna.isModerator ', 'pengguna.isModerator '], $moderasi[1], 'Pintu Moderasi harus dijaga peran di kedua menu.');
    }

    /**
     * Menu adalah perjalanan, bukan daftar tabel: TEPAT lima tab. Peran (Moderasi,
     * Admin) bukan tahap perjalanan dan tinggal di menu akun.
     */
    public function test_the_main_menu_is_five_surfaces_not_nine_nouns(): void
    {
        $isi = $this->daftarPermukaan();

        $this->assertSame(5, preg_match_all('/label: \'/', $isi), 'Menu utama wajib berisi tepat lima tab.');
        $this->assertStringNotContainsString('admin', $isi);
        $this->assertStringNotContainsString('moderation', $isi);

        // Menu akun ponsel tidak mengulang tab; dimulai dari Profil.
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $mulai = strpos($layout, '{terbuka ? (');
        $this->assertNotFalse($mulai, 'Menu akun ponsel tidak ditemukan.');
        $menuAkun = substr($layout, $mulai, strpos($layout, '</nav>', $mulai) - $mulai);

        $this->assertStringNotContainsString('permukaan.map', $menuAkun);
        $this->assertMatchesRegularExpression('/^[^<]*(?:<(?!TautanResponsif)[^<]*)*<TautanResponsif href=\{profile\.url\(\)\}>Profil</s', $menuAkun, 'Entri pertama menu akun ponsel harus Profil.');
    }

    /**
     * Blok `permukaan` di NavLinks.tsx saja, dari deklarasinya sampai penutup lariknya.
     */
    private function daftarPermukaan(): string
    {
        $isi = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $mulai = strpos($isi, 'export const permukaan = [');

        $this->assertNotFalse($mulai, 'Daftar permukaan tidak ditemukan di NavLinks.tsx.');

        return substr($isi, $mulai, strpos($isi, "\n];", $mulai) - $mulai);
    }

    /**
     * Seluruh objek satu tab di daftar permukaan, dari labelnya sampai label berikutnya.
     */
    private function aturan(string $label): string
    {
        preg_match("/label: '".preg_quote($label, '/')."'(.*?)(?=label: '|$)/s", $this->daftarPermukaan(), $cocok);

        $this->assertNotEmpty($cocok, "Tab {$label} tidak ditemukan.");

        return $cocok[1];
    }
}
