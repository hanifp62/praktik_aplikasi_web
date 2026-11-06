<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Hitungan Simpanan di kerangka (spec v3 bagian 5.2; rencana induk W2 butir 3).
 *
 * Header layar lebar dan kepala Jelajah menampilkan "Simpanan N" di setiap halaman, jadi
 * hitungannya props bersama. Tiga hal yang dijaga: angkanya sama dengan daftar di halaman
 * Simpanan (hanya milik sendiri, jalur arsip tidak dihitung), biayanya satu kueri berapa pun
 * jumlah simpanan, dan muat ulang parsial yang tidak memintanya tidak membayarnya.
 */
class SavedCountPropTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_count_matches_the_saved_page_and_belongs_to_its_owner(): void
    {
        $budi = User::factory()->create();
        $sari = User::factory()->create();
        [$satu, $dua, $arsip] = Trail::factory()->count(3)->create()->all();
        $arsip->forceFill(['archived_at' => now()])->save();

        foreach ([$satu, $dua, $arsip] as $jalur) {
            TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $jalur->id]);
        }

        TrailConsideration::create(['user_id' => $sari->id, 'trail_id' => $satu->id]);

        $this->actingAs($budi)->get(route('trails.saved'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', 2)->has('trails', 2));

        $this->actingAs($sari)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', 1));
    }

    /**
     * R-022: jalur yang ditarik dari terbit tapi belum diarsip tetap tampil di halaman
     * Simpanan (SavedRoutesController.php:29-35;
     * SavedRoutesTest::test_a_saved_trail_that_is_no_longer_published_cannot_be_picked_for_comparison).
     * Predikat aktifnya "belum diarsip", bukan "masih terbit", jadi hitungan kerangka
     * harus tetap mengikutkannya, sama seperti jumlah kartu yang dikirim halaman itu.
     */
    public function test_the_count_includes_a_trail_withdrawn_from_publication_but_not_yet_archived(): void
    {
        $budi = User::factory()->create();
        $terbit = Trail::factory()->published()->create();
        $ditarik = Trail::factory()->create(['is_published' => false]);
        $arsip = Trail::factory()->create();
        $arsip->forceFill(['archived_at' => now()])->save();

        foreach ([$terbit, $ditarik, $arsip] as $jalur) {
            TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $jalur->id]);
        }

        $this->actingAs($budi)->get(route('trails.saved'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) {
                $props = $page->toArray()['props'];
                $this->assertSame(2, count($props['trails']));
                $this->assertSame($props['jumlahSimpanan'], count($props['trails']));
            });
    }

    public function test_a_guest_carries_no_count(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', null));
    }

    public function test_the_count_costs_one_query_whatever_the_number_of_saved_trails(): void
    {
        $budi = User::factory()->create();

        foreach (Trail::factory()->count(25)->create() as $jalur) {
            TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $jalur->id]);
        }

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $this->assertSame(25, app(ConsiderationService::class)->jumlahSimpanan($budi));
        $this->assertSame(1, $kueri);
    }

    /**
     * Saringan Jelajah memakai muat ulang parsial (only: trails, menunggu, ...). Hitungan
     * yang malas tidak ikut dihitung di sana.
     *
     * Diperbarui perbaikan akhir W2 (F-5, tinjauan akhir M-4): asersi semula hanya memeriksa
     * bahwa prop itu tidak ada di jawaban, dan Inertia memang menyaring prop yang tidak
     * diminta walau prop itu dihitung eager, jadi test itu tetap hijau bila closure-nya diganti
     * nilai eager. Kini kueri yang berjalan selama muat ulang itu sendiri yang diperiksa,
     * mengikuti pola W2-4a di ActiveHikeStripTest.
     */
    public function test_a_partial_reload_that_does_not_ask_for_it_skips_it(): void
    {
        $respons = $this->actingAs(User::factory()->create())->get('/dashboard');

        $kueri = [];
        DB::listen(function ($query) use (&$kueri) {
            $kueri[] = $query->sql;
        });

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jumlahSimpanan', 0)
            ->reloadOnly('title', fn (AssertableInertia $muatUlang) => $muatUlang->missing('jumlahSimpanan')));

        $hitungan = collect($kueri)->filter(fn (string $sql) => str_contains($sql, 'trail_considerations'));

        $this->assertTrue($hitungan->isEmpty(), 'Muat ulang parsial yang tidak meminta jumlahSimpanan menjalankan: '.$hitungan->implode(' | '));
    }

    /**
     * Perbaikan akhir W2 (F-5). Kontrol pasangan test di atas: muat ulang parsial yang memang
     * meminta jumlahSimpanan menjalankan kueri hitungnya tepat sekali, jadi pemeriksaan kueri
     * di atas mengamati kueri yang benar.
     */
    public function test_a_partial_reload_that_asks_for_it_runs_the_count_query_once(): void
    {
        $budi = User::factory()->create();
        TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => Trail::factory()->create()->id]);

        $respons = $this->actingAs($budi)->get('/dashboard');

        $kueri = [];
        DB::listen(function ($query) use (&$kueri) {
            $kueri[] = $query->sql;
        });

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('jumlahSimpanan', fn (AssertableInertia $muatUlang) => $muatUlang->where('jumlahSimpanan', 1)));

        $this->assertCount(1, collect($kueri)->filter(fn (string $sql) => str_contains($sql, 'trail_considerations')));
    }

    /**
     * Perbaikan akhir W2 (F-9, tinjauan akhir N-1): mode lapangan tidak menggambar kerangka,
     * jadi tidak ada header atau kepala Jelajah yang membaca hitungan Simpanan. Penjaganya
     * sama dengan pendakianAktif (ActiveHikeStripTest::test_field_mode_itself_does_not_compute_it).
     */
    public function test_field_mode_does_not_run_the_count_query(): void
    {
        $budi = User::factory()->create();
        $trip = TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value]);
        TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $trip->trail_id]);

        $kueri = [];
        DB::listen(function ($query) use (&$kueri) {
            $kueri[] = $query->sql;
        });

        $this->actingAs($budi)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike')->where('jumlahSimpanan', null));

        $hitungan = collect($kueri)->filter(fn (string $sql) => str_contains($sql, 'trail_considerations'));

        $this->assertTrue($hitungan->isEmpty(), 'Mode lapangan menjalankan: '.$hitungan->implode(' | '));
    }

    /**
     * Header layar lebar memakai bentuk ringkas (ikon dan angka, kata dibacakan) di bawah
     * 1280 px; kepala Jelajah di ponsel memakai bentuk penuh seperti prototype 02 dan
     * menyembunyikannya mulai 1024 px karena header layar lebar sudah membawanya.
     */
    public function test_the_wide_header_and_the_jelajah_header_show_the_count(): void
    {
        $tautan = File::get(resource_path('js/layouts/TautanSimpanan.tsx'));

        $this->assertStringContainsString('href={saved.url()}', $tautan);
        $this->assertStringContainsString('min-h-11', $tautan);
        $this->assertStringContainsString('<Icon name="bookmark-simple" size={16} />', $tautan);
        $this->assertStringContainsString("<span className={kelas(ringkas && 'sr-only xl:not-sr-only')}>Simpanan</span>", $tautan);
        $this->assertStringContainsString('<span data-angka="">{jumlahSimpanan}</span>', $tautan);
        $this->assertStringContainsString('<span className="sr-only"> jalur tersimpan</span>', $tautan);
        $this->assertStringContainsString('<TautanSimpanan ringkas />', File::get(resource_path('js/layouts/KepalaLebar.tsx')));
        $this->assertStringContainsString('aksi={<TautanSimpanan className="lg:hidden" />}', File::get(resource_path('js/pages/Trails/Index.tsx')));
    }
}
