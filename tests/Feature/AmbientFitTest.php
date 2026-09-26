<?php

namespace Tests\Feature;

use App\Enums\RouteFitLabel;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kecocokan hadir di tempat orang menjelajah.
 *
 * Diukur sebelum diperbaiki: penjelasan kecocokan muncul di satu halaman dari dua belas,
 * dan halaman Jelajahi Jalur, yaitu tempat orang mendarat dari menu, tidak menampilkannya
 * sama sekali. Di situ aplikasi ini memang katalog, dan katalog memang terbaca sebagai
 * CRUD.
 */
class AmbientFitTest extends TestCase
{
    use RefreshDatabase;

    private function pendakiDenganProfil(): User
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

    private function jalurTerbit(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
    }

    public function test_the_browse_page_says_whether_each_trail_fits_the_reader(): void
    {
        $this->jalurTerbit();

        $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trails.data.0.fit.denganRencana', false)
                ->whereNot('trails.data.0.fit.label', null));
    }

    /**
     * R-004: label kecocokan yang boleh dilihat publik hanya tiga, COCOK, PERLU
     * PERSIAPAN, dan KURANG COCOK.
     *
     * Dijaga di tiga lapis, karena label keempat dapat menyelinap di salah satunya:
     * enum di server, teks yang dikirim ke halaman, dan tipe label di React yang
     * menentukan warna dan simbolnya. Ketiadaan penilaian ditulis "Tidak dinilai",
     * bukan label keempat.
     */
    public function test_only_the_three_public_fit_labels_reach_the_page(): void
    {
        $this->assertSame(['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK'], array_column(RouteFitLabel::cases(), 'value'));

        Trail::factory()->count(3)->for(Mountain::factory()->create())->published()->create();

        $baris = $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->viewData('page')['props']['trails']['data'];

        $this->assertNotEmpty($baris);

        $terbaca = array_map(fn (RouteFitLabel $l) => $l->label(), RouteFitLabel::cases());

        foreach ($baris as $satu) {
            $this->assertContains($satu['fit']['label'], ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK', null]);
            $this->assertContains($satu['fit']['labelText'], [...$terbaca, 'Tidak dinilai']);
        }

        $tipe = "'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null";
        $this->assertStringContainsString("export type LabelFit = {$tipe};", File::get(resource_path('js/components/ui/FitBadge.tsx')));
        $this->assertStringContainsString("label: {$tipe};", File::get(resource_path('js/components/trails/types.ts')));
    }

    /**
     * Label tidak pernah berdiri sendiri. Label tanpa alasan adalah vonis, dan §90
     * menuntut pembacanya memahami sebabnya tanpa membuka dokumentasi teknis.
     *
     * Alasannya dikirim bersama barisnya; tombol "Kenapa?" membukanya di halaman React
     * tanpa bolak-balik ke server, jadi yang dijaga di sini adalah alasannya ada dan
     * tidak kosong untuk setiap baris yang berlabel.
     */
    public function test_the_browse_page_ships_a_fit_reason_with_every_label(): void
    {
        $this->jalurTerbit();

        $data = $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->viewData('page')['props']['trails']['data'];

        $this->assertNotEmpty($data);

        foreach ($data as $baris) {
            $this->assertNotNull($baris['fit']);
            $this->assertNotSame('', trim($baris['fit']['alasan']), $baris['name'].' berlabel tanpa alasan.');
        }
    }

    /**
     * Pengguna yang profilnya belum lengkap tidak dinilai diam-diam.
     *
     * Menilai kecocokan tanpa profil menghasilkan label yang terlihat pasti dan berdasar
     * ketiadaan. Yang ditawarkan justru jalan keluarnya, yaitu melengkapi profil.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->jalurTerbit();

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.hasCompletedProfile', false)
                ->where('trails.data.0.fit', null));
    }

    /**
     * Skor internal tidak bocor lewat markup mana pun (BR-09).
     */
    public function test_no_internal_score_reaches_the_page(): void
    {
        $this->jalurTerbit();

        $isi = $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->getContent();

        $this->assertStringNotContainsString('internalScore', $isi);
        $this->assertStringNotContainsString('internal_score', $isi);
    }

    /**
     * Anggaran query tetap datar untuk pengguna yang kecocokannya benar-benar dinilai.
     *
     * Penjaga anggaran yang sudah ada memakai pengguna tanpa profil lengkap, sehingga
     * jalur kode penilaian kecocokan tidak pernah dijalankannya: ia hijau tanpa menjaga
     * apa pun. §96 adalah alasan TrailFitService dibangun batch, dan batasan yang tidak
     * dijaga hanya berlaku di atas kertas.
     */
    public function test_the_fit_path_does_not_grow_with_the_number_of_trails(): void
    {
        $user = $this->pendakiDenganProfil();
        $this->actingAs($user);

        $gunung = Mountain::factory()->create();
        Trail::factory()->count(4)->for($gunung)->published()->create();

        // Pemanasan: pengukuran pertama menghitung cache yang terisi, bukan pertumbuhan.
        $this->get(route('trails.index'));

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('trails.index'));
        $sedikit = count(DB::getQueryLog());

        Trail::factory()->count(20)->for($gunung)->published()->create();

        DB::flushQueryLog();
        $this->get(route('trails.index'));
        $banyak = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $sedikit, 'Tidak ada query sama sekali; pengukurannya tidak mengukur apa pun.');
        $this->assertLessThanOrEqual(
            $sedikit,
            $banyak,
            "Beban tumbuh ketika kecocokan dinilai: {$sedikit} lalu {$banyak}. Ada N+1 di jalur fit."
        );
    }

    /**
     * Lapisan "Mengapa" (§89) benar-benar terlihat pendaki, bukan cuma ada di kode.
     *
     * Diperiksa lewat permintaan HTTP sungguhan, bukan grep isi berkas: grep hanya
     * membuktikan sebuah string ada di suatu tempat, tidak pernah membuktikan pendaki
     * sungguh melihat sesuatu. Faktor pengalaman dipilih sebagai buktinya karena
     * CompatibilityScorer::score() selalu menghitungnya untuk setiap jalur, tidak
     * bersyarat pada goal.
     */
    public function test_the_trail_detail_page_shows_the_explanation_layer(): void
    {
        $trail = $this->jalurTerbit();

        $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Show')
                ->where('fit.faktor', fn ($faktor) => collect($faktor)->contains('label', 'Kesesuaian pengalaman')));

        $this->assertStringContainsString('Mengapa demikian', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Item D tinjauan akhir: jalur yang sama bisa terbaca "Cocok" di baris daftar
     * (yang menilai tanpa goal) dan "Perlu persiapan" di halaman detailnya (yang menilai
     * dengan rencana terbaru pengguna kalau ada), dan sebelum diperbaiki tidak ada
     * penanda di mana pun yang menyebut sebabnya -- terlihat seperti dua mesin yang tidak
     * sepakat, padahal dua pertanyaan yang berbeda. Pengguna di sini sengaja tidak punya
     * hiking goal, jadi halaman detail juga menilai tanpa goal dan wajib mengaku begitu
     * dengan kalimat yang sama seperti baris daftar.
     */
    public function test_the_trail_detail_page_states_it_is_showing_the_ambient_fit_without_a_goal(): void
    {
        $trail = $this->jalurTerbit();

        $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fit.denganRencana', false));

        $this->assertStringContainsString(
            "fit.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'",
            file_get_contents(resource_path('js/pages/Trails/Show.tsx'))
        );
    }

    /**
     * Item D, sisi lainnya: pengguna yang punya hiking goal melihat halaman detail
     * menilai DENGAN rencananya, dan halaman itu wajib menyebutnya -- kalimat yang sama
     * persis dengan yang dipakai halaman trip ("untuk rencana ini") supaya pembaca yang
     * pernah melihatnya di sana mengenalinya di sini.
     */
    public function test_the_trail_detail_page_states_it_is_showing_the_plan_aware_fit_with_a_goal(): void
    {
        $user = $this->pendakiDenganProfil();
        $trail = $this->jalurTerbit();
        HikingGoal::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fit.denganRencana', true));
    }

    /**
     * Halaman trip menampilkan kecocokan yang tajam, bukan yang dasar.
     *
     * Trip selalu punya goal (relasi hiking_goal_id), jadi variannya wajib
     * denganRencana: true, yaitu baris "untuk rencana ini" pada FitTrip, bukan
     * "Kecocokan dasar" yang dipakai halaman jelajah tanpa rencana.
     */
    public function test_the_trip_page_shows_the_plan_aware_fit_for_its_trail(): void
    {
        $user = $this->pendakiDenganProfil();
        $trail = $this->jalurTerbit();
        $goal = HikingGoal::factory()->for($user)->create();
        $trip = TripPlan::factory()->for($user)->create([
            'trail_id' => $trail->id,
            'hiking_goal_id' => $goal->id,
        ]);

        $this->actingAs($user)->get(route('trips.show', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Show')
                ->where('fit.denganRencana', true)
                ->where('fit.alasan', fn (?string $alasan) => filled($alasan)));

        // Halaman trip React: alasannya selalu dirender di samping label (§89).
        $komponen = File::get(resource_path('js/components/trips/FitTrip.tsx'));
        $this->assertStringContainsString('untuk rencana ini', $komponen);
        $this->assertStringContainsString('data-fit-reason', $komponen);
    }

    /**
     * Daftar trip membawa kecocokan dasar tanpa goal, sebentuk dengan halaman jelajah.
     *
     * Keputusan desainnya sudah diambil di Tugas 5: daftar trip adalah permukaan
     * pemindaian, dan kecocokan tajam yang sadar-rencana sudah tinggal di halaman trip.
     * Menyalakan kecocokan per-goal di sini berarti satu goal berbeda per baris, dan itu
     * batching yang berbeda sama sekali tanpa imbalan sepadan -- karena itu baris ini
     * tetap menampilkan "Kecocokan dasar" meski trip-nya sendiri punya goal.
     */
    public function test_the_trip_list_shows_the_ambient_fit_for_each_trip(): void
    {
        $user = $this->pendakiDenganProfil();
        $trail = $this->jalurTerbit();
        $goal = HikingGoal::factory()->for($user)->create();
        TripPlan::factory()->for($user)->create([
            'trail_id' => $trail->id,
            'hiking_goal_id' => $goal->id,
        ]);

        $this->actingAs($user)->get(route('trips.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Index')
                ->where('trips.data.0.fit.denganRencana', false)
                ->where('trips.data.0.fit.alasan', fn (?string $alasan) => filled($alasan)));

        $this->assertStringContainsString('Kecocokan dasar', File::get(resource_path('js/components/trips/FitTrip.tsx')));
    }

    /**
     * Item G tinjauan akhir: komponen baris kecocokan versi Blade membuang kelas yang
     * dipasang halaman trip di sekelilingnya (class="mb-6") diam-diam -- ruang antara
     * ringkasan kecocokan dan kartu di bawahnya hilang tanpa siapa pun memberi tahu.
     */
    public function test_the_trip_pages_fit_line_keeps_its_wrapper_class(): void
    {
        // Halaman trip kini React: ruang di bawah ringkasan kecocokan dioper sebagai
        // className, dan komponennya wajib menggabungkannya, bukan membuangnya.
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $komponen = File::get(resource_path('js/components/trips/FitTrip.tsx'));

        $this->assertStringContainsString('<FitTrip fit={fit} className="mb-6" />', $halaman);
        $this->assertMatchesRegularExpression('/className=\{kelas\([^)]*,\s*className\)\}/', $komponen);
    }

    /**
     * Anggaran query daftar trip tidak tumbuh mengikuti jumlah trip.
     *
     * Pola pengukurannya sama dengan test_the_fit_path_does_not_grow_with_the_number_of_trails:
     * sedikit lawan banyak sesudah pemanasan, bukan angka mutlak, karena angka mutlak
     * bergantung pada middleware dan konfigurasi test yang bisa berubah tanpa
     * berhubungan dengan N+1 di jalur ini.
     */
    public function test_the_trip_list_fit_path_does_not_grow_with_the_number_of_trips(): void
    {
        $user = $this->pendakiDenganProfil();

        TripPlan::factory()->for($user)->count(2)->create([
            'trail_id' => fn () => $this->jalurTerbit()->id,
        ]);

        $this->actingAs($user);

        // Pemanasan: pengukuran pertama menghitung cache yang terisi, bukan pertumbuhan.
        $this->get(route('trips.index'));

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('trips.index'));
        $sedikit = count(DB::getQueryLog());

        TripPlan::factory()->for($user)->count(15)->create([
            'trail_id' => fn () => $this->jalurTerbit()->id,
        ]);

        DB::flushQueryLog();
        $this->get(route('trips.index'));
        $banyak = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $sedikit, 'Tidak ada query sama sekali; pengukurannya tidak mengukur apa pun.');
        $this->assertLessThanOrEqual(
            $sedikit,
            $banyak,
            "Beban tumbuh ketika kecocokan dinilai: {$sedikit} lalu {$banyak}. Ada N+1 di jalur fit."
        );
    }
}
