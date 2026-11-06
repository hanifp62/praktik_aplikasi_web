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
    use SumberReact;

    /**
     * Cermin aturan `aktif` setiap tab di NavLinks.tsx, kata demi kata, untuk
     * test_a_page_and_its_parent_light_the_same_tab. Bila aturan di NavLinks berubah, test itu
     * gagal sampai cermin ini dan tabUntuk() diperbarui bersama.
     *
     * @var array<string, string>
     */
    private const AKTIF = [
        'Beranda' => "aktif: (p: string) => p === '/dashboard' || p.startsWith('/kabar')",
        'Jelajah' => "aktif: (p: string) => ((p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare')) || p.startsWith('/mountains/'),",
        'Trip' => "(p.startsWith('/trips') && !modePendakian(p)) || p.startsWith('/goals') || p.startsWith('/recommendations') || p.startsWith('/trails/compare'),",
        'Mendaki' => "aktif: (p: string) => p.startsWith('/mendaki') || modePendakian(p)",
        'Progres' => "aktif: (p: string) => p.startsWith('/progres') || p.startsWith('/history') || p.startsWith('/reports'),",
    ];

    /**
     * Induk yang datang dari props server, bukan dari rute Wayfinder: penilaian kecocokan
     * kembali ke rincian jalurnya (props jalur.url).
     *
     * @var array<string, string>
     */
    private const INDUK_DARI_PROPS = ['Trails/Fit.tsx' => 'trails.show'];

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
     * Tombol kembali tidak pernah memindah tab (tinjauan akhir W2, W2-F2): bila sebuah halaman
     * menyalakan tab, induk logisnya menyalakan tab yang sama. Sebelum W3, Banding menyalakan
     * Trip tetapi kembali ke Simpanan milik Jelajah. Halaman yang tidak menyalakan tab mana pun
     * (Profil, konsol, onboarding, kontribusi) bebas memilih induknya.
     *
     * Jalur halaman dibaca dari rute GET controller yang merendernya, dan jalur induknya dari
     * impor Wayfinder di halaman itu (`saved.url()` dari '@/routes/trails' adalah rute
     * trails.saved). Tab dihitung dari cermin aturan NavLinks.tsx, yang dicocokkan dulu dengan
     * sumbernya kata demi kata.
     */
    public function test_a_page_and_its_parent_light_the_same_tab(): void
    {
        foreach (self::AKTIF as $label => $sumber) {
            $this->assertStringContainsString($sumber, $this->aturan($label), "Aturan tab {$label} berubah; perbarui cermin AKTIF dan tabUntuk() di test ini.");
        }

        $this->assertStringContainsString('const modePendakian = (p: string) => /^\/trips\/[^/]+\/hike/.test(p);', File::get(resource_path('js/layouts/NavLinks.tsx')));

        $pelanggar = [];
        $diperiksa = [];
        $tanpaRute = [];

        foreach ($this->berkasTsx('pages') as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());
            $isi = $this->tanpaKomentarJs($berkas->getContents());
            $kepala = $this->tagBernama($isi, 'PageHeader');

            if ($kepala === [] || ! str_contains($kepala[0][0], ' induk={')) {
                continue;
            }

            $tabHalaman = $this->tabHalaman(substr($nama, 0, -4));

            if ($tabHalaman === false) {
                $tanpaRute[] = $nama;

                continue;
            }

            $diperiksa[] = $nama;

            if ($tabHalaman === null) {
                continue;
            }

            foreach ($this->ruteInduk($nama, $isi, $kepala[0][0]) as $rute) {
                $uri = app('router')->getRoutes()->getByName($rute)?->uri();

                if ($uri === null) {
                    $pelanggar[] = "{$nama}: rute induk {$rute} tidak ada";
                } elseif (($tabInduk = $this->tabUntuk($uri)) !== $tabHalaman) {
                    $pelanggar[] = "{$nama}: menyalakan {$tabHalaman}, tetapi induknya /{$uri} menyalakan ".($tabInduk ?? 'tidak ada tab');
                }
            }
        }

        $this->assertSame(['Dev/Primitif.tsx'], $tanpaRute, 'Hanya pameran primitif yang rutenya tidak terdaftar di test (hanya APP_ENV=local).');
        $this->assertContains('Trails/Compare.tsx', $diperiksa);
        $this->assertGreaterThan(30, count($diperiksa), 'Sapuan tidak menemukan halaman berinduk.');
        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
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
     * Halaman gunung milik Jelajah (spec v3 bagian 5.3, W3): gunung membuka jalurnya, dan
     * pembaca datang dari kartu atau rincian jalur. Path-nya di luar /trails, jadi tanpa
     * aturan sendiri tidak ada tab yang menyala di sana.
     */
    public function test_the_mountain_page_lights_jelajah(): void
    {
        $this->assertStringContainsString("p.startsWith('/mountains/')", $this->aturan('Jelajah'));

        foreach (['Beranda', 'Trip', 'Mendaki', 'Progres'] as $tab) {
            $this->assertStringNotContainsString('/mountains', $this->aturan($tab), "Tab {$tab} ikut menyala di halaman gunung.");
        }
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
     * detail jalur, penilaian kecocokan (W3), dan rencanakan trip. Halaman lain tetap
     * membawa bilah tab.
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

        $this->assertSame(['Trails/Fit.tsx', 'Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
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

    /**
     * Tab yang menyala di sebuah path, menurut cermin AKTIF; null bila tidak ada.
     */
    private function tabUntuk(string $uri): ?string
    {
        $p = '/'.ltrim((string) preg_replace('/\{[^}]+\}/', 'x', $uri), '/');
        $mendaki = preg_match('#^/trips/[^/]+/hike#', $p) === 1;

        $nyala = array_keys(array_filter([
            'Beranda' => $p === '/dashboard' || str_starts_with($p, '/kabar'),
            'Jelajah' => (($p === '/trails' || str_starts_with($p, '/trails/')) && ! str_starts_with($p, '/trails/compare')) || str_starts_with($p, '/mountains/'),
            'Trip' => (str_starts_with($p, '/trips') && ! $mendaki) || str_starts_with($p, '/goals') || str_starts_with($p, '/recommendations') || str_starts_with($p, '/trails/compare'),
            'Mendaki' => str_starts_with($p, '/mendaki') || $mendaki,
            'Progres' => str_starts_with($p, '/progres') || str_starts_with($p, '/history') || str_starts_with($p, '/reports'),
        ]));

        $this->assertLessThanOrEqual(1, count($nyala), "Dua tab menyala di {$p}.");

        return $nyala[0] ?? null;
    }

    /**
     * Tab halaman dari rute GET controller yang merendernya: null bila rutenya tidak
     * menyalakan tab, false bila tidak ada controller atau rute yang merendernya.
     */
    private function tabHalaman(string $komponen): string|false|null
    {
        $kelas = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $berkas) {
            if (str_contains($berkas->getContents(), "'{$komponen}'")) {
                $kelas[] = 'App\\Http\\Controllers\\'.str_replace(['/', '.php'], ['\\', ''], str_replace('\\', '/', $berkas->getRelativePathname()));
            }
        }

        $tab = [];

        foreach (app('router')->getRoutes()->getRoutes() as $rute) {
            if (in_array('GET', $rute->methods(), true) && in_array($rute->getControllerClass(), $kelas, true)) {
                $tab[] = $this->tabUntuk($rute->uri()) ?? 'tanpa tab';
            }
        }

        $tab = array_values(array_unique($tab));

        if ($tab === []) {
            return false;
        }

        $this->assertCount(1, $tab, "{$komponen} dirender controller yang rutenya menyalakan tab berbeda: ".implode(', ', $tab));

        return $tab[0] === 'tanpa tab' ? null : $tab[0];
    }

    /**
     * Nama rute induk sebuah halaman: setiap `x.url(...)` di prop induk dipetakan lewat
     * impornya ('@/routes/trails' dengan `saved` adalah trails.saved), atau dari
     * INDUK_DARI_PROPS bila induknya URL dari props server.
     *
     * @return list<string>
     */
    private function ruteInduk(string $nama, string $isi, string $tag): array
    {
        $mulai = strpos($tag, ' induk={') + 7;
        $kedalaman = 0;
        $akhir = $mulai;

        for ($i = $mulai; $i < strlen($tag); $i++) {
            $kedalaman += $tag[$i] === '{' ? 1 : ($tag[$i] === '}' ? -1 : 0);

            if ($kedalaman === 0) {
                $akhir = $i;

                break;
            }
        }

        preg_match_all('/\b([A-Za-z_]\w*)\.url\(/', substr($tag, $mulai, $akhir - $mulai + 1), $pemanggil);

        if ($pemanggil[1] === []) {
            $this->assertArrayHasKey($nama, self::INDUK_DARI_PROPS, "{$nama}: induk tanpa rute Wayfinder; sebut rutenya di INDUK_DARI_PROPS.");

            return [self::INDUK_DARI_PROPS[$nama]];
        }

        $impor = [];
        preg_match_all("/import \{([^}]*)\} from '@\/routes((?:\/[\w-]+)*)';/", $isi, $baris, PREG_SET_ORDER);

        foreach ($baris as [, $daftar, $modul]) {
            foreach (array_map('trim', explode(',', $daftar)) as $satu) {
                [$ekspor, $lokal] = array_pad(array_map('trim', explode(' as ', $satu)), 2, null);
                $impor[$lokal ?? $ekspor] = ltrim(str_replace('/', '.', $modul).'.'.$ekspor, '.');
            }
        }

        return array_values(array_unique(array_map(function (string $lokal) use ($impor, $nama): string {
            $this->assertArrayHasKey($lokal, $impor, "{$nama}: {$lokal}.url() tidak diimpor dari @/routes.");

            return $impor[$lokal];
        }, $pemanggil[1])));
    }
}
