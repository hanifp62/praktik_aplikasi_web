<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menu desktop dan menu ponsel harus menyajikan hal yang sama dalam urutan yang sama.
 *
 * Keduanya ditulis terpisah di berkas yang sama, jadi mudah bergeser tanpa ada yang
 * menyadarinya. Sebelum test ini, item kedua dan ketiga tertukar: pengguna yang
 * berpindah dari ponsel ke laptop menemukan menunya di tempat berbeda.
 */
class NavigationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_menus_list_the_same_destinations_in_the_same_order(): void
    {
        $isi = File::get(resource_path('views/livewire/layout/navigation.blade.php'));

        preg_match_all("/<x-nav-link :href=\"route\('([a-z.]+)'\)/", $isi, $desktop);
        preg_match_all("/<x-responsive-nav-link :href=\"route\('([a-z.]+)'\)/", $isi, $ponsel);

        $this->assertNotEmpty($desktop[1], 'Menu desktop tidak terbaca.');

        // Menu ponsel membawa satu entri tambahan di bagian akun: profil, yang di desktop
        // berada di dropdown dengan komponen berbeda. Yang dibandingkan adalah menu
        // utamanya, dan desktop harus menjadi awalan persis dari daftar ponsel.
        $this->assertSame(
            $desktop[1],
            array_slice($ponsel[1], 0, count($desktop[1])),
            'Urutan menu desktop dan ponsel berbeda.'
        );

        $this->assertContains('profile', $ponsel[1], 'Profil harus terjangkau dari menu ponsel.');
    }

    /**
     * Alur produknya menjelajah, menimbang, lalu berjalan. Urutan menunya mengikuti itu.
     *
     * Dua yang pertama adalah menemukan dan memilih, yang ketiga menjalankannya, dan dua
     * yang terakhir permukaan kembali, yaitu alasan membuka aplikasi ketika alurnya sudah
     * selesai. Keduanya berkelompok di ujung alih-alih menyela alurnya.
     *
     * Dua kali percobaan menempatkan permukaan kembali di tengah lolos ke dalam kode dan
     * ditangkap test ini, jadi urutannya bukan kerapian belaka.
     */
    public function test_the_order_follows_the_product_flow(): void
    {
        $isi = File::get(resource_path('views/livewire/layout/navigation.blade.php'));

        preg_match_all("/<x-nav-link :href=\"route\('([a-z.]+)'\)/", $isi, $cocok);

        $this->assertSame(
            ['trails.index', 'trails.compare', 'trips.index', 'progress', 'news'],
            array_slice($cocok[1], 0, 5)
        );
    }

    /**
     * Item G tinjauan akhir: Jelajah dijaga routeIs('trails.*'), yang juga mencocokkan
     * trails.compare -- jadi di halaman Pertimbangkan, KEDUA tab menyala bersamaan, dan
     * pembaca tidak tahu tab mana yang sesungguhnya sedang ia buka.
     */
    public function test_jelajah_is_not_active_on_the_compare_page(): void
    {
        $isi = $this->actingAs(User::factory()->create())
            ->get(route('trails.compare'))
            ->getContent();

        preg_match('/<a class="([^"]*)"[^>]*>\s*Jelajah/s', $isi, $jelajah);
        preg_match('/<a class="([^"]*)"[^>]*>\s*Pertimbangkan/s', $isi, $pertimbangkan);

        $this->assertNotEmpty($jelajah, 'Tautan Jelajah tidak ditemukan di halaman perbandingan.');
        $this->assertNotEmpty($pertimbangkan, 'Tautan Pertimbangkan tidak ditemukan di halaman perbandingan.');

        $this->assertStringNotContainsString('border-brand-600', $jelajah[1], 'Jelajah ikut menyala padahal pembaca sedang di halaman Pertimbangkan.');
        $this->assertStringContainsString('border-brand-600', $pertimbangkan[1], 'Pertimbangkan seharusnya menyala di halamannya sendiri.');
    }

    public function test_a_hiker_never_sees_the_admin_entry(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('admin.trails'), escape: false);
    }

    /**
     * Menu adalah perjalanan, bukan daftar tabel.
     *
     * Diukur sebelum diubah: sembilan kata benda di menu utama, sementara §7 menetapkan
     * satu loop delapan tahap. AllTrails memakai lima tab, Strava lima, Traveloka empat,
     * dan semuanya campuran satu permukaan temuan, satu milik-saya, satu tindakan, satu
     * identitas. Sembilan kata benda adalah struktur basis data yang bocor ke menu.
     *
     * Sebelum diperbaiki, test ini tidak pernah menghitung apa pun: assertStringContainsString
     * lolos meski ada tautan keenam yang menyelip, dan test_the_order_follows_the_product_flow
     * di atas juga tidak menangkapnya karena ia hanya menegaskan lima elemen PERTAMA lewat
     * array_slice, bukan jumlah totalnya. "Lima permukaan" wajib berarti TEPAT lima, bukan
     * "lima ini ada, mungkin ditemani yang lain".
     */
    public function test_the_main_menu_is_five_surfaces_not_nine_nouns(): void
    {
        $isi = File::get(
            resource_path('views/livewire/layout/navigation.blade.php')
        );

        // Peran, bukan tahap perjalanan: keduanya pindah ke menu profil.
        $this->assertStringNotContainsString("routeIs('admin.*')", $isi);
        $this->assertStringNotContainsString("routeIs('moderation.*')", $isi);

        foreach (['Jelajah', 'Pertimbangkan', 'Perjalanan', 'Progres', 'Kabar'] as $permukaan) {
            $this->assertStringContainsString($permukaan, $isi, "Permukaan {$permukaan} hilang dari menu.");
        }

        preg_match_all("/<x-nav-link :href=\"route\('([a-z.]+)'\)/", $isi, $desktop);
        preg_match_all("/<x-responsive-nav-link :href=\"route\('([a-z.]+)'\)/", $isi, $ponsel);

        $this->assertCount(5, $desktop[1], 'Menu desktop wajib berisi tepat lima permukaan, bukan lebih atau kurang.');

        // Menu ponsel membawa satu entri tambahan sesudah lima permukaan utama (profil,
        // lihat test_both_menus_... di atas). Posisi "profile" wajib tepat di indeks ke-5:
        // kalau bukan, ada permukaan keenam yang menyelip di antara lima utama dan profil.
        $posisiProfil = array_search('profile', $ponsel[1], true);
        $this->assertNotFalse($posisiProfil, 'Profil harus terjangkau dari menu ponsel.');
        $this->assertSame(5, $posisiProfil, 'Menu ponsel wajib berisi tepat lima permukaan utama sebelum Profil.');
    }
}
