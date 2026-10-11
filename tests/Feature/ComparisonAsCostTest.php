<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Membandingkan tanpa membuka halaman satu per satu.
 *
 * Ini ambang A3 spec, dan ia diambil langsung dari riset. Titik sakit terbesar AllTrails:
 * pengguna bolak-balik antar halaman jalur karena tidak ada cara melihat pilihan
 * berdampingan. Riset corong Traveloka menemukan kalimat yang nyaris sama pada tahap
 * membandingkan hotel.
 *
 * Di sini perbandingan sudah ada, tetapi hanya dapat dicapai dari alur rekomendasi, bukan
 * dari halaman jelajah tempat orang sebenarnya menimbang.
 */
class ComparisonAsCostTest extends TestCase
{
    use RefreshDatabase;

    private function pendaki(): User
    {
        $user = User::factory()->create();

        // Lewat factory, bukan array tangan. Kolomnya tidak bernama seperti dugaan:
        // completed_hikes_count, longest_hike_duration_minutes, highest_elevation_gain_m.
        // ProfileFactory juga sudah mengisi completed_at, yang menjadi syarat
        // hasCompletedProfile(); tanpa itu kecocokan tidak akan pernah dinilai.
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
    }

    /**
     * Perbandingan dapat dicapai dari halaman jelajah, bukan hanya dari rekomendasi. Sejak W3
     * jalannya lewat tautan Simpanan berhitungan di kepala Jelajah (spec v3 bagian 6.2) ke
     * aksi banding di Simpanan (bagian 6.6); baki Jelajah lama tidak ada di prototype.
     */
    public function test_comparison_is_reachable_from_the_browse_page(): void
    {
        $user = $this->pendaki();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->actingAs($user)
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', 2));
        $this->actingAs($user)
            ->get(route('trails.saved'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 2));

        $this->assertStringContainsString('<TautanSimpanan', File::get(resource_path('js/pages/Trails/Index.tsx')));
        $this->assertStringContainsString('compare.url(', File::get(resource_path('js/components/trails/banding/AksiPilihan.tsx')));
    }

    /**
     * Barisnya dimensi ongkos, bukan daftar kolom basis data.
     *
     * Tujuan pengguna yang sebenarnya, menurut riset AllTrails, adalah memperkirakan
     * berapa waktu dan tenaga yang harus ia keluarkan.
     */
    public function test_the_rows_are_the_dimensions_of_what_it_will_cost(): void
    {
        $user = $this->pendaki();
        $a = $this->jalur();
        $b = $this->jalur();

        $baris = $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $a->id.','.$b->id]))
            ->assertOk()
            ->viewData('page')['props']['baris'];

        $this->assertSame(
            ['Waktu', 'Jarak', 'Tanjakan', 'Kecuraman', 'Tuntutan teknis', 'Kerumitan navigasi', 'Air', 'Berkemah', 'Medan'],
            array_column($baris, 'label'),
            'Urutannya mengikuti ongkos: waktu dan tenaga lebih dulu.'
        );
    }

    /**
     * Setiap kolom membawa kecocokannya untuk pembaca, bukan angka telanjang.
     *
     * Ini modifikasi terhadap Traveloka, yang membandingkan hotel terhadap hotel. Di sini
     * jalur dibandingkan terhadap pembacanya, karena yang ditimbang bukan harga melainkan
     * apakah ia sanggup.
     */
    public function test_each_column_carries_its_fit_for_the_reader(): void
    {
        $user = $this->pendaki();
        $a = $this->jalur();
        $b = $this->jalur();

        $jalur = $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $a->id.','.$b->id]))
            ->assertOk()
            ->viewData('page')['props']['jalur'];

        foreach ($jalur as $kolom) {
            $this->assertNotNull($kolom['fit'], $kolom['name'].' tanpa kecocokan.');
            $this->assertNotSame('', trim($kolom['fit']['alasan']), $kolom['name'].' berlabel tanpa alasan.');
        }

        // Label ditampilkan lewat FitLine yang sama dengan baris daftar (penanda data-fit-label).
        $this->assertStringContainsString('<FitLine', file_get_contents(resource_path('js/components/trails/banding/TabelBanding.tsx')));
    }

    /**
     * Titipan W3 ke W5: "Kecocokan dasar" adalah kepala barisnya, jadi tidak diulang di setiap
     * sel, dan "Kenapa?" menyebut nama jalurnya untuk pembaca layar.
     */
    public function test_the_fit_cell_does_not_repeat_its_row_and_names_its_trail(): void
    {
        $sel = File::get(resource_path('js/components/trails/FitLine.tsx'));

        $this->assertStringNotContainsString("'Kecocokan dasar'", $sel);
        $this->assertStringContainsString('<span className="sr-only"> {nama}</span>', $sel);
        $this->assertStringContainsString('<FitLine fit={j.fit} trailId={j.id} nama={j.name} />', File::get(resource_path('js/components/trails/banding/TabelBanding.tsx')));
    }

    /**
     * Jalur berjarak nol tidak menjatuhkan halaman perbandingan.
     *
     * distance_km di-cast 'decimal:2' pada model Trail, sehingga nilai mentahnya adalah
     * string seperti "0.00". (bool) "0.00" bernilai true di PHP -- hanya "" dan "0" yang
     * falsy -- jadi guard yang menguji nilai mentah sebelum dicast ke float meloloskan
     * jalur berjarak nol, lalu membagi dengan 0.0. Itu DivisionByZeroError yang tidak
     * tertangkap, dan pendaki yang menimbangnya tidak dapat memulihkan diri karena
     * baki timbangannya tidak terlihat dari halaman yang sudah rusak.
     */
    public function test_a_zero_distance_trail_does_not_fatal_the_comparison_page(): void
    {
        $user = $this->pendaki();
        $a = Trail::factory()->for(Mountain::factory()->create())
            ->published()->create(['distance_km' => 0, 'elevation_gain_m' => 500]);
        $b = $this->jalur();

        $props = $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $a->id.','.$b->id]))
            ->assertOk()
            ->viewData('page')['props'];

        // Kecuraman jalur berjarak nol tidak dapat dihitung, dan itu dikatakan apa adanya.
        $kolomA = array_search($a->id, array_column($props['jalur'], 'id'), true);
        $kecuraman = collect($props['baris'])->firstWhere('label', 'Kecuraman');

        $this->assertSame('Belum tersedia', $kecuraman['nilai'][$kolomA]);
    }
}
