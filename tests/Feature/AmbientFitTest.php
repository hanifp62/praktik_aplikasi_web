<?php

namespace Tests\Feature;

use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $halaman = $this->actingAs($this->pendakiDenganProfil())->get(route('trails.index'));

        $halaman->assertOk();
        $halaman->assertSee('Kecocokan dasar');
    }

    /**
     * Label tidak pernah berdiri sendiri. Label tanpa alasan adalah vonis, dan §90
     * menuntut pembacanya memahami sebabnya tanpa membuka dokumentasi teknis.
     */
    public function test_the_label_never_appears_without_its_reason(): void
    {
        $this->jalurTerbit();

        $isi = $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->getContent();

        $this->assertSame(
            substr_count($isi, 'data-fit-label'),
            substr_count($isi, 'data-fit-reason'),
            'Setiap label kecocokan wajib ditemani satu alasan.'
        );
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

        $halaman = $this->actingAs(User::factory()->create())->get(route('trails.index'));

        $halaman->assertOk();
        $halaman->assertDontSee('data-fit-label', escape: false);
        $halaman->assertSee('Lengkapi profil');
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

        $halaman = $this->actingAs($this->pendakiDenganProfil())->get(route('trails.show', $trail));

        $halaman->assertOk();
        $halaman->assertSee('Mengapa demikian');
        $halaman->assertSee('Kesesuaian pengalaman');
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

        $halaman = $this->actingAs($this->pendakiDenganProfil())->get(route('trails.show', $trail));

        $halaman->assertOk();
        $halaman->assertSee('Kecocokan dasar');
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

        $halaman = $this->actingAs($user)->get(route('trails.show', $trail));

        $halaman->assertOk();
        $halaman->assertSee('untuk rencana ini');
        $halaman->assertDontSee('Kecocokan dasar');
    }

    /**
     * Halaman trip menampilkan kecocokan yang tajam, bukan yang dasar.
     *
     * Trip selalu punya goal (relasi hiking_goal_id), jadi variannya wajib
     * denganRencana: true, yaitu baris "untuk rencana ini" pada fit-line, bukan
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

        $halaman = $this->actingAs($user)->get(route('trips.show', $trip));

        $halaman->assertOk();
        $halaman->assertSee('untuk rencana ini');
        $halaman->assertSee('data-fit-reason', escape: false);
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

        $halaman = $this->actingAs($user)->get(route('trips.index'));

        $halaman->assertOk();
        $halaman->assertSee('data-fit-reason', escape: false);
        $halaman->assertSee('Kecocokan dasar');
    }

    /**
     * Item G tinjauan akhir: <x-ui.fit-line> tidak memakai $attributes->merge(), jadi
     * class="mb-6" yang dipasang trip-show.blade.php di sekelilingnya dibuang diam-diam
     * -- ruang antara ringkasan kecocokan dan kartu di bawahnya hilang tanpa siapa pun
     * memberi tahu.
     */
    public function test_the_trip_pages_fit_line_keeps_its_wrapper_class(): void
    {
        $user = $this->pendakiDenganProfil();
        $trail = $this->jalurTerbit();
        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $trail->id]);

        $isi = $this->actingAs($user)->get(route('trips.show', $trip))->getContent();

        $this->assertMatchesRegularExpression(
            '/class="[^"]*\bmb-6\b[^"]*"[^>]*>\s*<span[^>]*data-fit-label/s',
            $isi,
            'class="mb-6" dari trip-show.blade.php dibuang oleh fit-line yang tidak memakai $attributes->merge().'
        );
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
