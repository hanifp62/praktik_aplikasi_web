<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Data yang belum ada disajikan sebagai tahapan, bukan sebagai kegagalan.
 *
 * Premis produknya: pihak resmi dan ahli bersertifikatlah yang mengisi data ini, dan
 * sebagian besar belum sempat. Menampilkan "Belum tercatat" tanpa menyebut siapa yang
 * ditunggu membuat pendaki mengira aplikasinya yang rusak.
 *
 * Yang dijaga paling ketat di sini: kejujuran keadaan itu tidak boleh berubah menjadi
 * kebocoran. Jalur yang belum terbit tetap tidak boleh tampil seolah dapat dipakai.
 */
class AwaitingDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mountain_knows_which_authority_is_responsible_for_it(): void
    {
        $gunung = $this->gunungBerbadan('Balai Taman Nasional Gunung Merbabu');

        $this->assertSame('Balai Taman Nasional Gunung Merbabu', $gunung->responsibleAuthority()?->name);
    }

    /**
     * Badan sertifikasi mengesahkan orang, bukan menyatakan keadaan jalur. Ia tidak boleh
     * disebut sebagai pihak yang ditunggu untuk data jalur (§43).
     */
    public function test_a_certification_body_is_never_named_as_responsible_for_a_trail(): void
    {
        $gunung = Mountain::factory()->create();

        $lsp = Authority::create([
            'name' => 'Lembaga sertifikasi uji',
            'slug' => 'lsp-uji',
            'type' => AuthorityType::CERTIFICATION_BODY->value,
        ]);
        $lsp->mountains()->attach($gunung->id);

        $this->assertNull($gunung->fresh()->responsibleAuthority());
    }

    public function test_a_trail_lists_what_is_still_missing_in_words_a_hiker_understands(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->unpublished()->create([
            'distance_km' => null,
            'elevation_gain_m' => null,
            'estimated_duration_minutes' => null,
        ]);

        $kurang = $jalur->awaitingData();

        $this->assertNotEmpty($kurang);

        $gabungan = mb_strtolower(implode(' ', $kurang));
        $this->assertStringNotContainsString('data_source', $gabungan, 'Bahasa kurator, bukan bahasa pendaki.');
        $this->assertStringContainsString('jarak', $gabungan);
    }

    public function test_a_complete_trail_is_waiting_for_nothing(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create([
            'distance_km' => 9.4,
            'elevation_gain_m' => 1100,
            'estimated_duration_minutes' => 600,
        ]);

        $this->assertStringNotContainsString('jarak', mb_strtolower(implode(' ', $jalur->awaitingData())));
    }

    /**
     * Halaman rinci jalur yang belum terbit sudah dapat dibuka lewat tautan langsung,
     * karena mount() hanya menolak yang terarsip. Yang tampil harus keadaan menunggu,
     * bukan rincian setengah jadi yang terbaca seperti rincian utuh.
     */
    public function test_an_unpublished_trail_shows_the_waiting_state_not_a_half_page(): void
    {
        $gunung = $this->gunungBerbadan('Balai Taman Nasional Gunung Lawu');
        $jalur = Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Jalur Cemoro Sewu']);

        $this->actingAs(User::factory()->create())
            ->get('/trails/'.$jalur->slug)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Awaiting')
                ->where('trail.name', 'Jalur Cemoro Sewu')
                ->where('badan.nama', 'Balai Taman Nasional Gunung Lawu')
                ->where('menunggu', fn ($butir) => count($butir) > 0)
                ->missing('fit')
                ->missing('angkaKunci')
                ->missing('komunitas'));
    }

    /**
     * Kejujuran tidak boleh berubah menjadi undangan. Jalur yang belum terbit tidak
     * punya cukup data untuk direncanakan, jadi jalan menuju perencanaan ditutup.
     */
    public function test_an_unpublished_trail_offers_no_way_to_plan_a_trip(): void
    {
        $jalur = Trail::factory()->for($this->gunungBerbadan())->unpublished()->create();

        $this->actingAs(User::factory()->create())
            ->get('/trails/'.$jalur->slug)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trails/Awaiting'));

        $halaman = file_get_contents(resource_path('js/pages/Trails/Awaiting.tsx'));
        $this->assertStringNotContainsString('Buat rencana trip', $halaman);
        $this->assertStringNotContainsString('trips', $halaman);
    }

    public function test_a_published_trail_still_shows_its_full_detail(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunungBerbadan())->create([
            'distance_km' => 9.4,
            'elevation_gain_m' => 1100,
            'estimated_duration_minutes' => 600,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/trails/'.$jalur->slug)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Show')
                ->where('angkaKunci.0.nilai', '9,4 km')
                ->where('angkaKunci.1.nilai', '10 jam')
                ->where('angkaKunci.2.nilai', '1.100 m'));

        $this->assertStringContainsString('Buat rencana trip', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Penjagaan kebocoran pencarian dipertajam, bukan dilonggarkan.
     *
     * Dulu aturannya "jalur draft tidak boleh tampil sama sekali", karena ketika itu ia
     * tampil BERCAMPUR dengan hasil dan terbaca seperti jalur yang dapat dipakai.
     *
     * Sekarang ia boleh tampil, tetapi hanya di bagian terpisah yang menyatakan datanya
     * belum ada. Yang tetap terlarang persis seperti dulu: muncul di dalam daftar hasil.
     */
    public function test_an_unpublished_trail_never_appears_inside_the_results_list(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Rahasia']);
        Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Jalur Draft']);
        Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur Terbit']);

        $props = $this->actingAs(User::factory()->create())
            ->get('/trails?search=Rahasia')
            ->assertOk()
            ->viewData('page')['props'];

        $hasil = array_column($props['trails']['data'], 'name');

        $this->assertNotContains('Jalur Draft', $hasil, 'Draft bocor ke daftar hasil.');
        $this->assertContains('Jalur Terbit', $hasil);
        $this->assertSame(['Jalur Draft'], array_column($props['menunggu'], 'name'));
    }

    /**
     * Bagian terpisah itu tidak boleh menjadi pintu belakang: tidak ada karakteristik,
     * tidak ada jalan merencanakan.
     */
    public function test_the_waiting_section_exposes_nothing_beyond_the_name(): void
    {
        $gunung = $this->gunungBerbadan();
        Trail::factory()->for($gunung)->unpublished()->create([
            'name' => 'Jalur Draft',
            'distance_km' => 12.5,
        ]);

        $menunggu = $this->actingAs(User::factory()->create())->get('/trails')->assertOk()->viewData('page')['props']['menunggu'];

        $this->assertCount(1, $menunggu);
        $this->assertSame('Jalur Draft', $menunggu[0]['name']);
        $this->assertSame(['id', 'name', 'url', 'mountain', 'province'], array_keys($menunggu[0]), 'Karakteristik tidak boleh ikut dikirim.');
    }

    private function gunungBerbadan(string $nama = 'Balai Taman Nasional uji'): Mountain
    {
        $gunung = Mountain::factory()->create();

        $badan = Authority::create([
            'name' => $nama,
            'slug' => Str::slug($nama),
            'type' => AuthorityType::NATIONAL_PARK->value,
        ]);

        $badan->mountains()->attach($gunung->id);

        return $gunung->fresh();
    }
}
