<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menu ponsel dan menu layar lebar harus menyajikan hal yang sama dalam urutan yang sama.
 *
 * Pada navigasi Blade lama keduanya ditulis terpisah di berkas yang sama, dan item
 * kedua dan ketiga sempat tertukar: pengguna yang berpindah dari ponsel ke laptop
 * menemukan menunya di tempat berbeda. Navigasi React menutup kelas cacat itu dengan
 * satu daftar, `permukaan` di NavLinks.tsx, yang digambar oleh tab atas dan navigasi
 * bawah ponsel. Isi daftarnya mengikuti arsitektur informasi v2 pemilik (DK-25,
 * 25 September 2026): Home, Explore, Trips, Track, Progress; Profil dan Community bukan tab.
 *
 * Diperbarui W2 (spec v3 bagian 5, ruling W2-R1): kedua menu kini bilah tab pil ponsel dan
 * navigasi segmen header layar lebar, keduanya dari itemTab(). Menu hamburger ponsel
 * dilepas: Profil dibuka dari avatar, dan pintu Moderasi, Admin, serta Keluar tinggal di
 * menu avatar layar lebar dan di halaman Profil. Niat setiap test tetap sama.
 */
class NavigationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_menus_list_the_same_destinations_in_the_same_order(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $kepala = File::get(resource_path('js/layouts/KepalaLebar.tsx'));

        $this->assertSame(1, substr_count($layout, 'itemTab('), 'Daftar tab dihitung sekali per halaman.');
        $this->assertStringContainsString('const tab = itemTab(jalur);', $layout);
        $this->assertStringContainsString('<KepalaLebar item={tab} />', $layout, 'Navigasi segmen layar lebar tidak lagi digambar dari daftar yang sama.');
        $this->assertStringContainsString('{bilahTab ? <BilahTab item={tab} /> : null}', $layout, 'Bilah tab ponsel tidak lagi digambar dari daftar yang sama.');
        $this->assertStringContainsString('<NavigasiSegmen item={item} />', $kepala);
        $this->assertStringContainsString('{item.map((t) => (', $kepala);

        foreach ([$layout, $kepala] as $isi) {
            $this->assertStringNotContainsString('permukaan.map(', $isi, 'Ada menu permukaan yang dirakit sendiri di luar itemTab().');
        }

        // Profil bukan tab: avatar di kepala akar tab (ponsel) dan menu avatar (layar lebar).
        $this->assertStringContainsString('<TautanMenu href={profile.url()}>Profil</TautanMenu>', File::get(resource_path('js/layouts/MenuAkun.tsx')));
        $this->assertStringContainsString('{tempat.akar ? <TautanProfil className="lg:hidden" /> : null}', File::get(resource_path('js/components/ui/PageHeader.tsx')));
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
     * Menu React menampilkan keduanya hanya bila props bersama menyatakan perannya, jadi
     * yang dijaga adalah props yang dikirim server untuk pendaki biasa dan syarat di setiap
     * tempat pintunya muncul. Diperbarui W2: tempatnya kini menu avatar layar lebar
     * (MenuAkun.tsx) dan kartu Akun di Profil (PintuAkun.tsx), bukan dua menu di AppLayout.
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

        $pintu = File::get(resource_path('js/layouts/MenuAkun.tsx')).File::get(resource_path('js/components/akun/PintuAkun.tsx'));

        preg_match_all('/\{([^{}]*)\?\s*<TautanMenu href="\/admin\/trails">Admin</', $pintu, $admin);
        preg_match_all('/\{([^{}]*)\?\s*<TautanMenu href="\/moderation">Moderasi</', $pintu, $moderasi);

        $this->assertSame(['pengguna.isAdmin ', 'pengguna.isAdmin '], $admin[1], 'Pintu Admin harus dijaga peran di menu avatar dan di Profil.');
        $this->assertSame(['pengguna.isModerator ', 'pengguna.isModerator '], $moderasi[1], 'Pintu Moderasi harus dijaga peran di menu avatar dan di Profil.');
    }

    /**
     * Menu avatar diuji per peran (rencana induk W2 butir 3): props peran yang menyalakan
     * pintunya dikirim server sesuai peran sebenarnya, dan admin juga moderator.
     *
     * @return array<string, array{0: UserRole, 1: bool, 2: bool}>
     */
    public static function peran(): array
    {
        return [
            'pendaki' => [UserRole::HIKER, false, false],
            'moderator' => [UserRole::MODERATOR, true, false],
            'admin' => [UserRole::ADMIN, true, true],
        ];
    }

    #[DataProvider('peran')]
    public function test_each_role_gets_exactly_its_console_doors(UserRole $peran, bool $moderasi, bool $admin): void
    {
        $this->actingAs(User::factory()->create(['role' => $peran->value]))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.isModerator', $moderasi)
                ->where('auth.user.isAdmin', $admin));
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

        // Menu akun tidak mengulang tab; dimulai dari Profil (diperbarui W2: menu avatar).
        $menuAkun = File::get(resource_path('js/layouts/MenuAkun.tsx'));

        $this->assertStringNotContainsString('itemTab(', $menuAkun);
        $this->assertStringNotContainsString('permukaan', $menuAkun);
        $this->assertMatchesRegularExpression('/^[^<]*(?:<(?!TautanMenu)[^<]*)*<TautanMenu href=\{profile\.url\(\)\}>Profil</s', $menuAkun, 'Entri pertama menu akun harus Profil.');
    }

    /**
     * Menu avatar adalah popover asli: Escape, klik di luar, dan kembalinya fokus ke avatar
     * ditangani peramban, dan elemen popover tidak memakai kelas display yang akan
     * mengalahkan aturan peramban yang menyembunyikannya.
     */
    public function test_the_wide_avatar_menu_is_a_native_popover(): void
    {
        $menu = File::get(resource_path('js/layouts/MenuAkun.tsx'));

        $this->assertStringContainsString('popoverTarget="menu-akun"', $menu);
        $this->assertMatchesRegularExpression('/<div ref=\{menu\} id="menu-akun" popover="auto" className="([^"]*)"/', $menu);

        preg_match('/<div ref=\{menu\} id="menu-akun" popover="auto" className="([^"]*)"/', $menu, $kelas);

        // Lookbehind tanpa ':' sengaja: varian seperti lg:flex tetap harus tertangkap
        // (ruling W2-2d), sedangkan kata majemuk berhubung strip seperti overflow-hidden
        // tidak ikut tertangkap karena '-' masih ada di kelas yang dikecualikan.
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])(?:flex|grid|block|inline-flex|inline-block|hidden)(?![\w-])/', $kelas[1], 'Kelas display pada popover membuatnya tampil walau tertutup.');
        $this->assertStringContainsString("router.on('start'", $menu, 'Menu harus tertutup ketika kunjungan Inertia dimulai.');
        $this->assertStringContainsString('hidePopover()', $menu);
        $this->assertStringContainsString('<span className="sr-only">Menu akun {pengguna.name}</span>', $menu);
        $this->assertStringNotContainsString('role="menu"', $menu);
    }

    /**
     * Halaman tugas dengan bar aksi tetap menyembunyikan bilah tab (spec v3 bagian 5.1):
     * detail jalur dan rencanakan trip (penilaian kecocokan menyusul di W3). Halaman lain
     * tetap membawa bilah tab.
     */
    public function test_only_task_pages_hide_the_tab_bar(): void
    {
        $tanpaBilah = [];

        foreach (File::allFiles(resource_path('js/pages')) as $berkas) {
            if (str_contains($berkas->getContents(), '<AppLayout bilahTab={false}>')) {
                $tanpaBilah[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        sort($tanpaBilah);

        $this->assertSame(['Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
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
