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
     * Alur produknya rencana dulu, baru menjelajah jalur. Urutan menunya mengikuti itu.
     */
    public function test_the_order_follows_the_product_flow(): void
    {
        $isi = File::get(resource_path('views/livewire/layout/navigation.blade.php'));

        preg_match_all("/<x-nav-link :href=\"route\('([a-z.]+)'\)/", $isi, $cocok);

        // Progres berada sesudah riwayat karena ia agregat dari riwayat, bukan langkah
        // tersendiri dalam alurnya. Menempatkannya sebelum riwayat pernah lolos ke
        // dalam kode dan ditangkap test ini.
        $this->assertSame(
            ['dashboard', 'goals.create', 'trails.index', 'trips.index', 'history', 'progress'],
            array_slice($cocok[1], 0, 6)
        );
    }

    public function test_a_hiker_never_sees_the_admin_entry(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('admin.trails'), escape: false);
    }
}
