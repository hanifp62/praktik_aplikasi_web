<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Daftar rute tersimpan.
 *
 * Sumbernya simpanan di basis data (trail_considerations), sumber yang sama dengan baki
 * di Jelajah. Versi sebelumnya membaca sesi, sehingga daftarnya kosong lagi setiap kali
 * pendaki keluar: Logout memanggil Session::invalidate().
 *
 * Halamannya React (Inertia): data diuji lewat props yang dikirim server, dan teks yang
 * dirender klien dijaga dari sumber halamannya.
 */
class SavedRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create($atribut);
    }

    private function daftar(User $user): TestResponse
    {
        return $this->actingAs($user)->get(route('trails.saved'))->assertOk();
    }

    private function sumber(string $berkas = 'js/pages/Trails/Saved.tsx'): string
    {
        return File::get(resource_path($berkas));
    }

    public function test_the_list_shows_each_saved_trail_with_distance_duration_and_difficulty(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->easy()->published()->create([
            'name' => 'Jalur Selo',
            'distance_km' => 12.5,
            'estimated_duration_minutes' => 390,
        ]);
        app(ConsiderationService::class)->toggle($user, $trail);

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trails/Saved')
            ->where('title', 'Rute Tersimpan')
            ->where('trails.0.name', 'Jalur Selo')
            ->where('trails.0.jarak', '12,5 km')
            ->where('trails.0.durasi', '6,5 j')
            ->where('trails.0.technical', 'Rendah'));
    }

    /**
     * Kriteria spesifikasi: jalur tersimpan tetap ada setelah keluar lalu masuk kembali.
     */
    public function test_saved_trails_are_still_listed_after_logging_out_and_back_in(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur(['name' => 'Jalur Wekas']);
        $this->actingAs($user)->post(route('trails.timbang', $trail->id));

        $this->actingAs($user)->post(route('logout'));
        $this->assertGuest();

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page->where('trails.0.name', 'Jalur Wekas'));
    }

    public function test_trails_saved_by_another_hiker_are_not_listed(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($saya, $this->jalur(['name' => 'Jalur Milik Saya']));
        $service->toggle($orangLain, $this->jalur(['name' => 'Jalur Milik Orang Lain']));

        $nama = array_column($this->daftar($saya)->viewData('page')['props']['trails'], 'name');

        $this->assertSame(['Jalur Milik Saya'], $nama);
    }

    /**
     * Terbaru dulu, urutan yang sama dengan "tiga simpanan terbaru" di halaman Banding.
     */
    public function test_the_list_is_newest_first(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur(['name' => 'Jalur Disimpan Pertama']));
        $service->toggle($user, $this->jalur(['name' => 'Jalur Disimpan Kedua']));

        $nama = array_column($this->daftar($user)->viewData('page')['props']['trails'], 'name');

        $this->assertSame(['Jalur Disimpan Kedua', 'Jalur Disimpan Pertama'], $nama);
    }

    public function test_an_empty_list_explains_how_to_save_a_trail(): void
    {
        $this->daftar(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 0));

        $this->assertStringContainsString('Belum ada rute tersimpan', $this->sumber());
        $this->assertStringContainsString('Cari jalur untuk disimpan', $this->sumber());
    }

    public function test_a_guest_is_redirected_to_the_login_page(): void
    {
        $this->get(route('trails.saved'))->assertRedirect(route('login'));
    }

    /**
     * Nama jalur tidak hanya ditulis admin: pemegang kredensial Ahli juga mengirimnya.
     * Props membawa teks mentah, JSON halaman menyandikan tandanya, dan React merendernya
     * sebagai teks (tanpa dangerouslySetInnerHTML).
     */
    public function test_a_trail_name_is_escaped(): void
    {
        $user = User::factory()->create();
        app(ConsiderationService::class)->toggle($user, $this->jalur(['name' => 'Jalur <script>alert(1)</script>']));

        $respons = $this->daftar($user);

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('trails.0.name', 'Jalur <script>alert(1)</script>'));
        $respons->assertDontSee('<script>alert(1)</script>', escape: false);

        foreach (['js/pages/Trails/Saved.tsx', 'js/components/trails/banding/KartuSimpanan.tsx'] as $berkas) {
            $this->assertStringNotContainsString('dangerouslySetInnerHTML', $this->sumber($berkas));
        }
    }

    /**
     * Batas simpanan dan batas banding disebut sebelum ditabrak, keduanya dari config.
     */
    public function test_the_list_states_its_size_against_the_configured_limits(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails', 2)
            ->where('batas', 50)
            ->where('batasBanding', 3));
        $this->assertStringContainsString('rute tersimpan, maksimal {batas}.', $this->sumber());
    }

    /**
     * R-022: jalur yang ditarik dari terbit tetap tampil bersama catatannya, tetapi tidak
     * dapat dicentang untuk banding, dan alasannya disebut di kartunya.
     */
    public function test_a_saved_trail_that_is_no_longer_published_cannot_be_picked_for_comparison(): void
    {
        $user = User::factory()->create();
        $ditarik = $this->jalur(['name' => 'Jalur Ditarik']);
        app(ConsiderationService::class)->toggle($user, $ditarik);
        $ditarik->update(['is_published' => false]);

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.0.name', 'Jalur Ditarik')
            ->where('trails.0.terbit', false));

        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');
        $this->assertStringContainsString('!jalur.terbit', $kartu);
        $this->assertStringContainsString('Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.', $kartu);
        $this->assertStringContainsString('disabled={alasanTidakDapatDipilih !== null}', $kartu);
        $this->assertStringContainsString('trails.filter((t) => t.terbit)', $this->sumber());
    }

    /**
     * Regresi 26 September 2026: halaman ini mengimpor `remove` dari keluaran Wayfinder
     * yang basi (`@/routes/trails/compare`), sehingga "Hapus" mengirim DELETE ke alamat
     * yang tidak dilayani. Rute hapus simpanan adalah trails.saved.remove.
     */
    public function test_removal_goes_through_the_saved_list_route(): void
    {
        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');

        $this->assertStringContainsString("import { remove } from '@/routes/trails/saved';", $kartu);
        $this->assertStringNotContainsString('@/routes/trails/compare', $kartu);
        $this->assertStringNotContainsString('@/routes/trails/compare', $this->sumber());
    }

    /**
     * DK-05: yang dicentang dihitung terhadap batas banding dari config, dan batasnya
     * disebut sebelum ditabrak.
     */
    public function test_the_selection_states_its_count_against_the_comparison_limit(): void
    {
        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');

        $this->assertStringContainsString('Dipilih {jumlah} dari {maks}.', $aksi);
        $this->assertStringContainsString('Pilih minimal 2 jalur untuk membandingkan.', $aksi);
        $this->assertStringContainsString('Batas banding tercapai.', $aksi);
        $this->assertStringContainsString('maks={batasBanding}', $this->sumber());
    }

    /**
     * Tanpa centang, tautannya telanjang: halaman Banding lalu membandingkan tiga simpanan
     * terbaru. Dengan centang, pilihan dibawa di ?trails=.
     */
    public function test_saved_trails_can_be_compared_from_the_list(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 2));

        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');
        $this->assertStringContainsString('compare.url()', $aksi);
        $this->assertStringContainsString("compare.url({ query: { trails: dipilih.join(',') } })", $aksi);
        $this->assertStringContainsString('<AksiPilihan', $this->sumber());
    }

    public function test_a_single_saved_trail_explains_what_comparing_needs(): void
    {
        $user = User::factory()->create();
        app(ConsiderationService::class)->toggle($user, $this->jalur());

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 1));
        $this->assertStringContainsString('Simpan satu jalur lagi untuk membandingkan.', $this->sumber());
        $this->assertStringContainsString('tersedia >= 2', $this->sumber());
    }

    /**
     * F5 (antislop R-26, kontrol harus berfungsi): dua simpanan tetapi hanya satu yang
     * masih terbit. Hanya satu kotak yang dapat dicentang di sini, jadi kontrol pilihan
     * tidak berguna dan tidak boleh tampil; banding butuh minimal dua jalur terbit.
     * Gerbangnya harus menghitung simpanan yang terbit (tersedia), bukan trails.length
     * yang ikut menghitung simpanan yang ditarik dari terbit.
     */
    public function test_the_selection_control_is_hidden_when_only_one_saved_trail_is_still_published(): void
    {
        $user = User::factory()->create();
        $terbit = $this->jalur(['name' => 'Jalur Terbit']);
        $ditarik = $this->jalur(['name' => 'Jalur Ditarik']);
        $service = app(ConsiderationService::class);
        $service->toggle($user, $terbit);
        $service->toggle($user, $ditarik);
        $ditarik->update(['is_published' => false]);

        $respons = $this->daftar($user);
        $respons->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 2));

        $status = array_column($respons->viewData('page')['props']['trails'], 'terbit');
        $this->assertEqualsCanonicalizing([true, false], $status);

        $this->assertStringContainsString('tersedia >= 2', $this->sumber());
        $this->assertStringNotContainsString('trails.length >= 2', $this->sumber());
    }

    /**
     * Cacat yang pernah ditutup: simpanan tidak dapat dicapai dari menunya sendiri
     * (HANDOVER.md §7). Daftar ini wajib punya pintu dari dua tempat pendaki berada.
     */
    public function test_the_jelajah_tray_links_to_the_list(): void
    {
        $user = User::factory()->create();
        app(ConsiderationService::class)->toggle($user, $this->jalur());

        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('ditimbang', 1));
        $this->assertStringContainsString('href={saved.url()}', $this->sumber('js/components/trails/ConsiderationTray.tsx'));
    }

    public function test_the_compare_page_links_to_the_list(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->actingAs($user)->get(route('trails.compare'))->assertInertia(fn (AssertableInertia $page) => $page->has('jalur', 2));
        $this->assertStringContainsString('href={saved.url()}', $this->sumber('js/pages/Trails/Compare.tsx'));
    }

    /**
     * Jalur dihapus dari simpanan di halaman ini, lewat aksi yang menyebut dirinya
     * penghapusan, bukan sakelar berlabel "Tersimpan".
     */
    public function test_a_trail_can_be_removed_from_the_list(): void
    {
        $user = User::factory()->create();
        $tetap = $this->jalur(['name' => 'Jalur Tetap']);
        $dihapus = $this->jalur(['name' => 'Jalur Dihapus']);
        $service = app(ConsiderationService::class);
        $service->toggle($user, $tetap);
        $service->toggle($user, $dihapus);

        $this->actingAs($user)
            ->from(route('trails.saved'))
            ->delete(route('trails.saved.remove', $dihapus->id))
            ->assertRedirect(route('trails.saved'))
            ->assertSessionHas('pengumuman', 'Jalur Dihapus dihapus dari simpanan.');

        $nama = array_column($this->daftar($user)->viewData('page')['props']['trails'], 'name');

        $this->assertSame(['Jalur Tetap'], $nama);
        $this->assertStringContainsString('remove.url({ trail: ', $this->sumber('js/components/trails/banding/KartuSimpanan.tsx'));
    }

    /**
     * "Hapus" adalah penghapusan, bukan sakelar: mengulangnya tidak mengembalikan jalur.
     */
    public function test_removing_the_same_trail_twice_does_not_bring_it_back(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        $service = app(ConsiderationService::class);
        $service->toggle($user, $a);
        $service->toggle($user, $b);

        $this->actingAs($user)->delete(route('trails.saved.remove', $a->id))->assertRedirect();
        $this->actingAs($user)->delete(route('trails.saved.remove', $a->id))->assertRedirect();

        $this->assertSame([$b->id], $service->simpanan($user)->pluck('trail_id')->all());
    }

    /**
     * IDOR: id jalur datang dari klien, tetapi baris yang dihapus selalu milik pengguna
     * yang masuk.
     */
    public function test_removing_never_touches_another_hikers_list(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $jalur = $this->jalur();
        app(ConsiderationService::class)->toggle($orangLain, $jalur);

        $this->actingAs($saya)->delete(route('trails.saved.remove', $jalur->id))->assertRedirect();

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $orangLain->id, 'trail_id' => $jalur->id]);
    }

    public function test_a_guest_cannot_remove_anything(): void
    {
        $user = User::factory()->create();
        $jalur = $this->jalur();
        app(ConsiderationService::class)->toggle($user, $jalur);

        $this->delete(route('trails.saved.remove', $jalur->id))->assertRedirect(route('login'));

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $jalur->id]);
    }
}
