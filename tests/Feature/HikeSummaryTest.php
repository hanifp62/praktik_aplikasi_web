<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Ringkasan pendakian (spec v3 bagian 6.13, prototype 12; rencana induk W6 butir 9).
 *
 * Peak-end rule mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya diingat.
 * Sejak W6 halamannya punya dua varian (selesai dan dibatalkan, DK-50), angka dari rekaman
 * bila ada dan dari data jalur bila tidak, dengan "Ubah angka"; dibanding pendakian sendiri
 * saja (R-015); dan tanpa persen persiapan (R-005), yang sebelumnya tampil sebagai "80%".
 * Seluruh isinya berasal dari data yang sudah tersimpan.
 */
class HikeSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-20 08:00:00', 'UTC'));
    }

    /**
     * Budi menyelesaikan Jalur Selo (naik 1.000 m, 9,7 km sekali jalan) 15 September; sesi
     * mode lapangan pukul 04.00 sampai 12.30 UTC dengan jeda 30 menit.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function pendakianSelesai(
        CompletionState $hasil = CompletionState::COMPLETED,
        bool $denganSesi = true,
        ?int $posTercapai = 3,
        ?User $user = null,
        int $naik = 1000,
        string $nama = 'Jalur Selo',
        ?Trail $trail = null,
    ): array {
        $user ??= User::factory()->create();

        if ($trail === null) {
            $trail = Trail::factory()->create(['name' => $nama, 'elevation_gain_m' => $naik, 'distance_km' => 9.7]);

            foreach (range(1, 4) as $urutan) {
                Checkpoint::factory()->for($trail)->create([
                    'sequence' => $urutan,
                    'name' => 'Pos '.$urutan,
                    'elevation_m' => 1200 + $urutan * 200,
                    // Berkoordinat, karena tanpa PostGIS geometri jalurnya null dan pos
                    // adalah satu-satunya yang dapat menggambar peta.
                    'latitude' => -7.45 - $urutan * 0.01,
                    'longitude' => 110.44,
                ]);
            }
        }

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
            'planned_date' => '2026-09-15',
        ]);

        if ($denganSesi) {
            HikingSession::create([
                'trip_plan_id' => $trip->id,
                'user_id' => $user->id,
                'status' => HikingSessionStatus::COMPLETED->value,
                'started_at' => Carbon::parse('2026-09-15 04:00:00', 'UTC'),
                'ended_at' => Carbon::parse('2026-09-15 12:30:00', 'UTC'),
                'paused_seconds' => 1800,
                'reached_checkpoint_sequence' => $posTercapai,
            ]);
        }

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 80,
            'personal_notes' => 'Kabut turun sejak Pos 3.',
            'completed_at' => Carbon::parse('2026-09-15 12:30:00', 'UTC'),
        ]);

        return [$user, $trip->fresh()];
    }

    private function buka(User $user, TripPlan $trip): TestResponse
    {
        return $this->actingAs($user)->get(route('history.summary', $trip));
    }

    public function test_it_shows_what_the_hike_actually_was(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/HikeSummary')
            ->where('title', 'Hasil pendakian Jalur Selo')
            ->where('varian', 'selesai')
            ->where('jalur.nama', 'Jalur Selo')
            ->where('hasil', 'Selesai sampai target')
            ->where('catatan', 'Kabut turun sejak Pos 3.'));
    }

    /**
     * Tanpa rekaman jejak di Memount: angka dari data jalur, disebut begitu, dengan jarak
     * pulang-pergi (9,7 km sekali jalan menjadi 19,4 km), dan waktu bergerak dari sesi dikurangi jeda
     * (8 jam 30 menit dikurangi 30 menit).
     *
     * Diubah sisipan W6-P2 dengan alasan (rancangan W6-P2 bagian 4, ruling W6-P2h): titik jejak kini
     * baru dikirim sesudah pendakian berakhir, jadi ringkasan pertama sering dirender saat rekaman
     * masih di perangkat. "Tanpa rekaman jejak" salah di cabang itu; "di Memount" benar di semua
     * cabang. Judul lama "Rekam jejak mati" juga tidak tepat untuk keadaan itu.
     */
    public function test_without_a_track_the_numbers_come_from_the_trail_and_say_so(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.jarak', "19,4\u{a0}km")
            ->where('angka.naik', "↑1.000\u{a0}m")
            ->where('angka.waktu', "8\u{a0}jam")
            ->where('angka.tertinggi', "2.000\u{a0}m")
            ->where('angka.sumber', 'jalur')
            ->where('angka.keterangan', 'Tanpa rekaman jejak di Memount, angka ini diisi dari data jalur, dengan jarak pulang-pergi lewat jalur yang sama. Ubah bila berbeda.')
            ->where('angka.awal', ['jarak_km' => 19.4, 'naik_m' => 1000, 'waktu_menit' => 480, 'tertinggi_m' => 2000]));
    }

    public function test_with_a_track_the_numbers_come_from_the_recording(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        foreach ([[-7.4500, 1500], [-7.4590, 1700], [-7.4680, 1650], [-7.4770, 1900]] as $i => [$lintang, $tinggi]) {
            HikeTrackPoint::create(['hiking_session_id' => $trip->hikingSession->id, 'recorded_at' => Carbon::parse('2026-09-15 05:00:00', 'UTC')->addMinutes($i * 20), 'latitude' => $lintang, 'longitude' => 110.44, 'elevation_m' => $tinggi]);
        }

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.jarak', "3,0\u{a0}km")
            ->where('angka.naik', "↑450\u{a0}m")
            ->where('angka.tertinggi', "1.900\u{a0}m")
            ->where('angka.sumber', 'jejak')
            ->where('angka.keterangan', 'Dari rekaman jejak Anda. Ubah bila berbeda.'));
    }

    /**
     * "Ubah angka": angka pendaki menggantikan angka turunan dan disebut sumbernya. Hanya
     * pemilik, dengan rentang yang masuk akal, dan dibatasi laju.
     */
    public function test_the_hiker_can_correct_the_numbers(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->put(route('history.numbers.update', $trip), ['jarak_km' => 19.4, 'naik_m' => 1660, 'waktu_menit' => 665, 'tertinggi_m' => 2958])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHas('status', 'Angka pendakian diperbarui.');

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.jarak', "19,4\u{a0}km")
            ->where('angka.naik', "↑1.660\u{a0}m")
            ->where('angka.waktu', "11\u{a0}jam 5\u{a0}menit")
            ->where('angka.tertinggi', "2.958\u{a0}m")
            ->where('angka.sumber', 'pendaki')
            ->where('angka.keterangan', 'Angka ini Anda ubah sendiri.'));
    }

    public function test_corrected_numbers_are_validated_and_owned(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->put(route('history.numbers.update', $trip), ['jarak_km' => -3, 'naik_m' => 99999, 'waktu_menit' => 'lama', 'tertinggi_m' => 7000])
            ->assertSessionHasErrors(['jarak_km', 'naik_m', 'waktu_menit', 'tertinggi_m']);

        $this->actingAs(User::factory()->create())
            ->put(route('history.numbers.update', $trip), ['jarak_km' => 5])
            ->assertForbidden();

        $this->assertNull($trip->history->fresh()->recorded_distance_m);
    }

    /**
     * Ruling W6-6j (M07-M09, daftar periksa butir 1): nilai TEPAT SESUDAH batas atasnya ditolak,
     * bukan hanya nilai yang jauh melampaui (seperti 99999 pada test di atas, yang juga menembus
     * batas yang dinaikkan mutan). Batasnya sendiri: jarak_km <= 200, tertinggi_m >= 0,
     * waktu_menit <= 7200 (UpdateHikeNumbersRequest.php).
     */
    public function test_corrected_numbers_reject_values_just_past_their_bounds(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->put(route('history.numbers.update', $trip), ['jarak_km' => 200.1, 'tertinggi_m' => -1, 'waktu_menit' => 7201])
            ->assertSessionHasErrors(['jarak_km', 'tertinggi_m', 'waktu_menit']);
    }

    /**
     * Ruling W6-6j (M26, daftar periksa butir 8): rute ini dibatasi laju seperti disebut
     * docblock HasilPendakian.php. Melewati batasnya menolak permintaan ke-31 dalam satu menit.
     */
    public function test_number_updates_are_rate_limited(): void
    {
        [$user, $trip] = $this->pendakianSelesai();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->put(route('history.numbers.update', $trip), ['naik_m' => 1000]);
        }

        $this->actingAs($user)
            ->put(route('history.numbers.update', $trip), ['naik_m' => 1000])
            ->assertStatus(429);
    }

    /**
     * Ruling W6-6j (M10): tanpa riwayat, rute ini tidak punya apa pun untuk diubah.
     */
    public function test_numbers_cannot_be_set_without_a_history_row(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->put(route('history.numbers.update', $trip), ['naik_m' => 1000])
            ->assertNotFound();
    }

    /**
     * Ruling W6-6j (M11): cabang kosong berlaku untuk SELURUH pendakian yang tidak tuntas tanpa
     * jejak, bukan hanya ABANDONED. PARTIAL (berbalik dekat tujuan) sama-sama tidak diketahui
     * jaraknya tanpa jejak; angka jalur penuh akan melebih-lebihkannya sama seperti ABANDONED.
     */
    public function test_a_partial_hike_without_a_track_has_unknown_numbers_too(): void
    {
        [$user, $trip] = $this->pendakianSelesai(hasil: CompletionState::PARTIAL, posTercapai: 3);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.sumber', 'kosong')
            ->where('angka.keterangan', 'Tanpa rekaman jejak di Memount, jarak dan tanjakan pendakian yang berbalik tidak diketahui. Isi sendiri bila Anda mencatatnya.')
            ->where('angka.jarak', null)
            ->where('angka.naik', null)
            ->where('angka.tertinggi', null));
    }

    /**
     * Props baris perangkat (sisipan W6-P2, ruling W6-P2h): id sesi pendakian ini, untuk titik yang
     * masih di perangkat, dan apakah perekaman jejak pendaki menyala sekarang. Hanya id, tanpa
     * koordinat. Tanpa sesi (mode lapangan tidak pernah dibuka) id-nya null, dan pendaki tanpa
     * preferensi tersimpan dibaca mati (bawaan).
     */
    public function test_the_summary_carries_the_session_id_and_the_recording_switch(): void
    {
        [$user, $trip] = $this->pendakianSelesai();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'record_track' => true]);

        $this->buka($user->fresh(), $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sesiId', $trip->hikingSession->id)
            ->where('rekamJejak', true));

        [$tanpaSesi, $tripTanpaSesi] = $this->pendakianSelesai(denganSesi: false);

        $this->buka($tanpaSesi, $tripTanpaSesi)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sesiId', null)
            ->where('rekamJejak', false));
    }

    /**
     * Dibanding pendakian sendiri (R-015): tangga dari riwayat pendaki ini saja, kalimat
     * selisih dari tanjakan tertinggi sebelumnya, dan tidak pernah pendaki lain.
     *
     * Diperbarui untuk ruling W6-6a: setiap baris sekarang membawa `id` dan `gunung` (nama
     * gunung acak dari pendakianSelesai() dibaca langsung dari trail yang dibuat), konsisten
     * dengan ruling W5-6a TanggaTanjakan Beranda. Keputusan lama (hanya nama+m+teks+ini) tidak
     * dilonggarkan, hanya diperluas.
     *
     * Ruling W6-6d/I-2 (bentuk probe P7 peninjau): `completed_at` "Jalur orang lain" dibuat
     * LEBIH AWAL daripada pendakian yang diringkas, bukan kebetulan sama (bawaan
     * pendakianSelesai() dahulu membuat semua riwayat bertanggal sama, 2026-09-15 12:30 UTC,
     * sehingga saring waktu `<` di HasilPendakian::tangga() SENDIRIAN sudah menyingkirkan
     * "Jalur orang lain" tanpa bantuan saring user_id; test ini tidak pernah membuktikan saring
     * penggunanya bekerja). Dengan tanggal lebih awal ini, membuang saring user_id akan
     * membocorkan "Jalur orang lain" (2.500 m) ke tangga.baris.
     *
     * Diperbarui untuk ruling W6-6h: angka dan satuan di tangga.kalimat kini diikat U+00A0
     * ("+960 m", "700 m"), bukan spasi biasa; kata dan angkanya tidak berubah.
     */
    public function test_the_hike_is_compared_with_the_hikers_own_history_only(): void
    {
        $budi = User::factory()->create();
        [, $prau] = $this->pendakianSelesai(user: $budi, naik: 700, nama: 'Prau');
        [, $andong] = $this->pendakianSelesai(user: $budi, naik: 380, nama: 'Andong');
        [, $orangLain] = $this->pendakianSelesai(naik: 2500, nama: 'Jalur orang lain');
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $prau->history->update(['completed_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC')]);
        $andong->history->update(['completed_at' => Carbon::parse('2026-07-05 09:00:00', 'UTC')]);
        $orangLain->history->update(['completed_at' => Carbon::parse('2026-06-01 09:00:00', 'UTC')]);

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tangga.baris', [
                ['id' => $andong->trail->id, 'nama' => 'Andong', 'gunung' => $andong->trail->mountain->name, 'm' => 380, 'teks' => "380\u{a0}m", 'ini' => false],
                ['id' => $prau->trail->id, 'nama' => 'Prau', 'gunung' => $prau->trail->mountain->name, 'm' => 700, 'teks' => "700\u{a0}m", 'ini' => false],
                ['id' => $trip->trail->id, 'nama' => 'Gede', 'gunung' => $trip->trail->mountain->name, 'm' => 1660, 'teks' => "1.660\u{a0}m", 'ini' => true],
            ])
            // Ruling W6-6n (M-9): kalimat menyebut gunung juga, bukan hanya nama jalur.
            ->where('tangga.kalimat', "+960\u{a0}m dari tanjakan tertinggi Anda sebelumnya (Prau, ".$prau->trail->mountain->name.", 700\u{a0}m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.")
            ->where('pencapaian', ["Tanjakan pertama di atas 1.000\u{a0}m"]));
    }

    /**
     * Ruling W6-6a (R-001, konsisten dengan ruling W5-6a Beranda/TanggaTanjakan): trails.name
     * tidak unik. Dua jalur bernama sama di gunung berbeda dengan tanjakan sama persis tidak
     * boleh menyatu jadi satu anak tangga; id dan gunung membedakannya meski nama dan m identik.
     *
     * Diperluas untuk ruling W6-6n: kalimat tangga lengkap juga diperiksa, karena di sinilah
     * nama jalur saja ambigu. Pemegang rekor pada seri adalah id jalur terkecil (Gunung Slamet).
     */
    public function test_two_trails_with_the_same_name_on_different_mountains_are_distinct_rungs(): void
    {
        $budi = User::factory()->create();
        $gunungA = Mountain::factory()->create(['name' => 'Gunung Slamet']);
        $gunungB = Mountain::factory()->create(['name' => 'Gunung Sindoro']);
        $jalurA = Trail::factory()->create(['name' => 'Jalur Utama', 'mountain_id' => $gunungA->id, 'elevation_gain_m' => 900]);
        $jalurB = Trail::factory()->create(['name' => 'Jalur Utama', 'mountain_id' => $gunungB->id, 'elevation_gain_m' => 900]);

        [, $tripA] = $this->pendakianSelesai(user: $budi, trail: $jalurA);
        [, $tripB] = $this->pendakianSelesai(user: $budi, trail: $jalurB);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $tripA->history->update(['completed_at' => Carbon::parse('2026-07-05 09:00:00', 'UTC')]);
        $tripB->history->update(['completed_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC')]);

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tangga.baris', [
                ['id' => $jalurA->id, 'nama' => 'Jalur Utama', 'gunung' => 'Gunung Slamet', 'm' => 900, 'teks' => "900\u{a0}m", 'ini' => false],
                ['id' => $jalurB->id, 'nama' => 'Jalur Utama', 'gunung' => 'Gunung Sindoro', 'm' => 900, 'teks' => "900\u{a0}m", 'ini' => false],
                ['id' => $trip->trail->id, 'nama' => 'Gede', 'gunung' => $trip->trail->mountain->name, 'm' => 1660, 'teks' => "1.660\u{a0}m", 'ini' => true],
            ])
            ->where('tangga.kalimat', "+760\u{a0}m dari tanjakan tertinggi Anda sebelumnya (Jalur Utama, Gunung Slamet, 900\u{a0}m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan."));

        // Nama gunung mendisambiguasi dua jalur bernama sama (ruling W6-6a); dibaca langsung
        // dari sumber JSX supaya mutasi yang membuang label gunung (M39) kelihatan merah.
        $this->assertStringContainsString('{b.gunung}', File::get(resource_path('js/components/community/BandingDiriSendiri.tsx')));

        // Kunci React memakai id (ruling W6-6a), bukan nama+m yang baru dibuktikan bisa identik
        // di atas; mutasi yang mengembalikan kunci ke nama+m membuat baris ini merah.
        $this->assertStringContainsString('key={`${b.id}-${b.ini', File::get(resource_path('js/components/community/BandingDiriSendiri.tsx')));
    }

    /**
     * Ruling W6-6n (M-9): dua jalur bernama sama di dua gunung juga pada cabang "Setara" dan
     * "tetap", bukan hanya "+X m". Pemegang rekor pada seri adalah id jalur terkecil (Gunung
     * Slamet, dibuat lebih dulu), dan gunungnya tertulis di kalimat supaya tidak ambigu.
     */
    public function test_the_ladder_sentence_names_the_mountain_of_a_record_holder_sharing_its_name(): void
    {
        foreach ([
            900 => "Setara dengan tanjakan tertinggi Anda sebelumnya (Jalur Utama, Gunung Slamet, 900\u{a0}m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.",
            700 => "Tanjakan tertinggi Anda tetap Jalur Utama (Gunung Slamet, 900\u{a0}m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.",
        ] as $naik => $kalimat) {
            $budi = User::factory()->create();
            $slamet = Mountain::where('name', 'Gunung Slamet')->first() ?? Mountain::factory()->create(['name' => 'Gunung Slamet']);
            $sindoro = Mountain::where('name', 'Gunung Sindoro')->first() ?? Mountain::factory()->create(['name' => 'Gunung Sindoro']);
            $jalurA = Trail::factory()->create(['name' => 'Jalur Utama', 'mountain_id' => $slamet->id, 'elevation_gain_m' => 900]);
            $jalurB = Trail::factory()->create(['name' => 'Jalur Utama', 'mountain_id' => $sindoro->id, 'elevation_gain_m' => 900]);
            [, $tripA] = $this->pendakianSelesai(user: $budi, trail: $jalurA);
            [, $tripB] = $this->pendakianSelesai(user: $budi, trail: $jalurB);
            $tripA->history->update(['completed_at' => Carbon::parse('2026-07-05 09:00:00', 'UTC')]);
            $tripB->history->update(['completed_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC')]);
            [, $trip] = $this->pendakianSelesai(user: $budi, naik: $naik, nama: 'Gede');

            $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page->where('tangga.kalimat', $kalimat));
        }
    }

    /**
     * Ruling W6-6g (M-2), probe P3a: tanjakan TEPAT 1.000 m tidak "di atas 1.000 m". Tonggak
     * yang dilewati dengan tegas adalah 500 m.
     */
    public function test_a_climb_of_exactly_a_milestone_is_not_above_it(): void
    {
        [$user, $trip] = $this->pendakianSelesai(naik: 1000, nama: 'Tepat Seribu');

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pencapaian', ["Tanjakan pertama di atas 500\u{a0}m"]));
    }

    /**
     * Ruling W6-6g (M-2), probe P3b: tonggak 1.000 m yang sebelumnya hanya TEPAT disentuh tetap
     * terbuka. Pendakian 1.660 m sesudah pendakian 1.000 m adalah tanjakan pertama yang benar-
     * benar di atas 1.000 m.
     */
    public function test_a_milestone_touched_earlier_is_still_crossed_by_a_higher_climb(): void
    {
        $budi = User::factory()->create();
        [, $seribu] = $this->pendakianSelesai(user: $budi, naik: 1000, nama: 'Tepat Seribu');
        $seribu->history->update(['completed_at' => Carbon::parse('2026-08-01 09:00:00', 'UTC')]);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pencapaian', ["Tanjakan pertama di atas 1.000\u{a0}m"]));
    }

    /**
     * Tangga ringkasan memakai ukuran tangga Beranda: tanjakan jalur, satu anak tangga per jalur.
     * Angka yang diubah pendaki hanya mengganti panel angka, jadi Beranda dan ringkasan tidak
     * menggambar dua tangga yang berbeda untuk riwayat yang sama (ruling W6-R13).
     *
     * Diperbarui untuk ruling W6-6a: baris membawa `id` dan `gunung` (lihat juga
     * test_two_trails_with_the_same_name_on_different_mountains_are_distinct_rungs di atas).
     */
    public function test_the_ladder_uses_the_same_measure_as_the_home_ladder(): void
    {
        $budi = User::factory()->create();
        [, $prau] = $this->pendakianSelesai(user: $budi, naik: 700, nama: 'Prau');
        [, $prauLagi] = $this->pendakianSelesai(user: $budi, trail: $prau->trail);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $prau->history->update(['completed_at' => Carbon::parse('2026-07-05 09:00:00', 'UTC')]);
        $prauLagi->history->update(['completed_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC')]);
        $trip->history->update(['recorded_gain_m' => 1720, 'numbers_edited_at' => now()]);

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.naik', "↑1.720\u{a0}m")
            ->where('tangga.baris', [
                ['id' => $prau->trail->id, 'nama' => 'Prau', 'gunung' => $prau->trail->mountain->name, 'm' => 700, 'teks' => "700\u{a0}m", 'ini' => false],
                ['id' => $trip->trail->id, 'nama' => 'Gede', 'gunung' => $trip->trail->mountain->name, 'm' => 1660, 'teks' => "1.660\u{a0}m", 'ini' => true],
            ]));
    }

    /**
     * Ruling W6-6j (M12, M17): pendakian sebelumnya yang BERBALIK (tidak tuntas) tidak boleh
     * masuk tangga walau tanjakannya tinggi, karena tidak membuktikan tanjakannya pernah
     * dituntaskan. Dengan satu-satunya riwayat tuntas sebelumnya tersingkir, kalimatnya juga
     * membuktikan cabang "pendakian tuntas pertama" (bukan hanya selamat dari dua baris lain).
     */
    public function test_previous_unfinished_climbs_do_not_enter_the_ladder(): void
    {
        $budi = User::factory()->create();
        [, $gagal] = $this->pendakianSelesai(user: $budi, hasil: CompletionState::ABANDONED, naik: 5000, nama: 'Jalur Tinggi Gagal');
        $gagal->history->update(['completed_at' => Carbon::parse('2026-08-01 09:00:00', 'UTC')]);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tangga.baris', [
                ['id' => $trip->trail->id, 'nama' => 'Gede', 'gunung' => $trip->trail->mountain->name, 'm' => 1660, 'teks' => "1.660\u{a0}m", 'ini' => true],
            ])
            ->where('tangga.kalimat', 'Ini pendakian tuntas pertama yang tercatat di riwayat Anda. Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.'));
    }

    /**
     * Ruling W6-6j (M13): pendakian sebelumnya yang tuntas tetapi jalurnya tidak punya
     * elevation_gain_m tidak boleh masuk tangga sebagai anak tangga 0 m (R-007: tidak diketahui
     * bukan nol).
     */
    public function test_previous_climbs_without_a_recorded_gain_do_not_enter_the_ladder(): void
    {
        $budi = User::factory()->create();
        $trailTanpaGain = Trail::factory()->create(['name' => 'Jalur Tanpa Data', 'elevation_gain_m' => null]);
        [, $tanpaGain] = $this->pendakianSelesai(user: $budi, trail: $trailTanpaGain);
        $tanpaGain->history->update(['completed_at' => Carbon::parse('2026-08-01 09:00:00', 'UTC')]);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tangga.baris', [
                ['id' => $trip->trail->id, 'nama' => 'Gede', 'gunung' => $trip->trail->mountain->name, 'm' => 1660, 'teks' => "1.660\u{a0}m", 'ini' => true],
            ]));
    }

    /**
     * Ruling W6-6j (M14): paling banyak tiga jalur tertinggi sebelumnya ditampilkan (BARIS_TANGGA
     * - 1), bukan seluruhnya. Dengan empat riwayat sebelumnya (400, 500, 600, 700 m), yang
     * terendah (400 m, "Satu") tersingkir dan hanya tiga tertinggi yang tampil.
     */
    public function test_the_ladder_shows_at_most_three_previous_climbs(): void
    {
        $budi = User::factory()->create();
        [, $satu] = $this->pendakianSelesai(user: $budi, naik: 400, nama: 'Satu');
        [, $dua] = $this->pendakianSelesai(user: $budi, naik: 500, nama: 'Dua');
        [, $tiga] = $this->pendakianSelesai(user: $budi, naik: 600, nama: 'Tiga');
        [, $empat] = $this->pendakianSelesai(user: $budi, naik: 700, nama: 'Empat');

        foreach ([$satu, $dua, $tiga, $empat] as $i => $riwayat) {
            $riwayat->history->update(['completed_at' => Carbon::parse('2026-07-0'.($i + 1).' 09:00:00', 'UTC')]);
        }

        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('tangga.baris', 4)
            ->where('tangga.baris.0.nama', 'Dua')
            ->where('tangga.baris.1.nama', 'Tiga')
            ->where('tangga.baris.2.nama', 'Empat')
            ->where('tangga.baris.3.nama', 'Gede'));
    }

    /**
     * Ruling W6-6j (M15): tonggak yang dilaporkan adalah yang TERTINGGI yang baru dilewati
     * (->last() atas tonggak terurut naik), bukan yang terendah. Pendakian tuntas pertama
     * sekaligus di 2.500 m melewati 500, 1.000, dan 2.000 m bersamaan; pencapaiannya harus
     * menyebut 2.000 m, bukan 500 m.
     */
    public function test_the_milestone_reported_is_the_highest_one_crossed(): void
    {
        [$user, $trip] = $this->pendakianSelesai(naik: 2500, nama: 'Jalur Tinggi');

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pencapaian', ["Tanjakan pertama di atas 2.000\u{a0}m"]));
    }

    /**
     * Ruling W6-6j (M16): cabang "Setara" untuk tanjakan yang PERSIS sama dengan rekor
     * sebelumnya, dibedakan dari "+X m" (lebih tinggi) dan "tetap" (lebih rendah). Diperbarui
     * untuk ruling W6-6h: "1.660 m" di kalimat kini diikat U+00A0, bukan spasi biasa.
     */
    public function test_the_ladder_recognises_an_exact_tie(): void
    {
        $budi = User::factory()->create();
        [, $sebelumnya] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede Pertama');
        $sebelumnya->history->update(['completed_at' => Carbon::parse('2026-08-01 09:00:00', 'UTC')]);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede Kedua');

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where(
                'tangga.kalimat',
                'Setara dengan tanjakan tertinggi Anda sebelumnya (Gede Pertama, '.$sebelumnya->trail->mountain->name.", 1.660\u{a0}m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan."
            ));
    }

    /**
     * Ruling W6-6j (M18): cabang "tetap" untuk pendakian yang LEBIH RENDAH dari rekor
     * sebelumnya; nama dan gunung pemegang rekor tetap disebut, bukan pendakian ini. Diperbarui
     * untuk ruling W6-6h: "1.660 m" di kalimat kini diikat U+00A0, bukan spasi biasa.
     */
    public function test_the_ladder_keeps_the_record_holder_named_when_this_climb_is_lower(): void
    {
        $budi = User::factory()->create();
        [, $pemegangRekor] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');
        $pemegangRekor->history->update(['completed_at' => Carbon::parse('2026-08-01 09:00:00', 'UTC')]);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 700, nama: 'Prau');

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where(
                'tangga.kalimat',
                'Tanjakan tertinggi Anda tetap Gede ('.$pemegangRekor->trail->mountain->name.", 1.660\u{a0}m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan."
            ));
    }

    /**
     * Ruling W6-6j (M19): pendakian yang TUNTAS tetapi jalurnya tidak punya elevation_gain_m
     * tidak dapat dibandingkan sama sekali (tangga null), dengan kalimat yang menyebut itu,
     * berbeda dari kalimat untuk pendakian yang berbalik (banding() cabang lain).
     */
    public function test_a_completed_hike_without_a_recorded_gain_cannot_be_compared(): void
    {
        $trail = Trail::factory()->create(['name' => 'Jalur Tanpa Data', 'elevation_gain_m' => null]);
        [$user, $trip] = $this->pendakianSelesai(trail: $trail);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tangga', null)
            ->where('banding', 'Tanjakan jalur ini belum tercatat, jadi belum dapat dibandingkan dengan riwayat Anda.'));
    }

    /**
     * Ruling W6-6j (M20, BR-11): tanpa profil pendaki SAMA SEKALI (bukan hanya tanpa
     * experience_level), kalimatnya tetap tidak mengklaim perubahan tingkat pengalaman.
     */
    public function test_the_experience_level_sentence_without_any_profile_at_all(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tingkat.kalimat', 'Riwayat pendakian tidak mengubah tingkat pengalaman Anda; Anda sendiri yang mengisinya di Profil.'));
    }

    /**
     * Ruling W6-6l (M-7): tautan "Buka Profil" memakai target sentuh 44 px (inline-flex
     * min-h-11), sama dengan tautan setara di Beranda (beranda/TanggaTanjakan.tsx).
     */
    public function test_the_profile_link_meets_the_touch_target_size(): void
    {
        $sumber = File::get(resource_path('js/components/community/BandingDiriSendiri.tsx'));

        $this->assertMatchesRegularExpression('/<Tautan href=\{tingkat\.url\}[^>]*inline-flex min-h-11/', $sumber);
    }

    /**
     * Ruling W6-6j (M32): peringatan foto yang gagal dibersihkan juga tiba di ringkasan, tidak
     * hanya di halaman riwayat (HikingHistoryController, sudah diuji ConditionReportPageTest).
     */
    public function test_a_dropped_photo_warning_reaches_the_summary_too(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->withSession(['foto-tidak-disertakan' => 'Foto tidak dapat diproses dan tidak disertakan. Laporan tetap tersimpan.'])
            ->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('peringatanFoto', 'Foto tidak dapat diproses dan tidak disertakan. Laporan tetap tersimpan.'));
    }

    /**
     * Ruling W6-6b (daftar periksa mutu butir 6): 'tanggal' tingkat atas memakai zona gunung
     * lewat Timezone::forTrail(), sama dengan laporan.formulir.tanggal, bukan UTC server.
     * 20.00 UTC adalah 03.00 keesokan harinya di WIB (+7, bawaan MountainFactory): tanggal UTC
     * dan tanggal WIB berbeda satu hari.
     */
    public function test_the_date_uses_the_mountains_timezone_not_utc(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Jalur Malam']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::COMPLETED->value, 'planned_date' => '2026-09-15']);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse('2026-09-15 20:00:00', 'UTC'),
        ]);

        $this->buka($user, $trip->fresh())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tanggal', 'Rabu, 16 September 2026'));
    }

    /**
     * Berbalik di jalur bukan tanjakan yang dituntaskan: tidak masuk tangga dan tidak memberi
     * pencapaian, dan kalimatnya tidak menghukum. Tanpa jejak, angka jalur penuh akan
     * melebih-lebihkan pendakian yang berbalik, jadi yang tidak diketahui tetap kosong (R-007).
     */
    public function test_an_abandoned_hike_is_named_with_its_reason_and_kept_out_of_the_ladder(): void
    {
        [$user, $trip] = $this->pendakianSelesai(hasil: CompletionState::ABANDONED, posTercapai: 2);
        $trip->update(['cancellation_reason' => CancellationReason::WEATHER->value]);

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasil', 'Dibatalkan di jalur · Cuaca')
            ->where('posTerjauh', 'Pos 2')
            ->where('angka.jarak', null)
            ->where('angka.naik', null)
            ->where('angka.tertinggi', null)
            ->where('angka.waktu', "8\u{a0}jam")
            ->where('angka.sumber', 'kosong')
            ->where('tangga', null)
            ->where('pencapaian', [])
            ->where('banding', 'Pendakian yang berbalik sebelum tujuan tidak dihitung sebagai tanjakan yang dituntaskan. Keputusan berbalik tetap tercatat apa adanya.'));
    }

    /**
     * BR-11: riwayat tidak menaikkan tingkat pengalaman; pendaki sendiri yang mengubahnya.
     */
    public function test_the_experience_level_is_never_raised_by_the_history(): void
    {
        [$user, $trip] = $this->pendakianSelesai();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tingkat.kalimat', 'Tingkat pengalaman Anda tetap '.ExperienceLevel::BEGINNER->label().' sampai Anda mengubahnya sendiri di Profil.')
            ->where('tingkat.url', route('profile')));
    }

    /**
     * R-005: tidak ada persen di ringkasan, termasuk persiapan yang dahulu tampil "80%".
     */
    public function test_no_percent_reaches_the_summary(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $props = json_encode($this->buka($user, $trip)->viewData('page')['props'], JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('%', $props);
    }

    /**
     * Varian dibatalkan (DK-50): trip yang dibatalkan sebelum mendaki punya ringkasan pendek
     * dengan alasannya, tanpa angka dan tanpa formulir laporan.
     */
    public function test_a_cancelled_trip_has_a_short_summary_with_its_reason(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value, 'cancellation_reason' => CancellationReason::OFFICIAL_STATUS->value, 'planned_date' => '2026-09-25']);

        $this->buka($trip->user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('varian', 'batal')
            ->where('hasil', 'Dibatalkan · Status resmi')
            ->where('angka', null)
            ->where('laporan', null));
    }

    /**
     * Ruling W6-6f (M-1), cabang belum dimulai: tanpa sesi pendakian. Judul tabnya "Trip {jalur}
     * dibatalkan", bukan "Hasil pendakian", dan tidak ada pos terjauh.
     */
    public function test_a_trip_cancelled_before_the_hike_started_has_its_own_title(): void
    {
        $trail = Trail::factory()->create(['name' => 'Jalur Batal Awal']);
        $trip = TripPlan::factory()->create(['trail_id' => $trail->id, 'status' => TripStatus::CANCELLED->value, 'cancellation_reason' => CancellationReason::WEATHER->value, 'planned_date' => '2026-09-25']);

        $this->buka($trip->user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Trip Jalur Batal Awal dibatalkan')
            ->where('varian', 'batal')
            ->where('sesiDimulai', false)
            ->where('posTerjauh', null)
            ->where('jejak', null));
    }

    /**
     * Ruling W6-6f (M-1), cabang sudah dimulai: TripLifecycle::cancel() tidak menutup sesi, jadi
     * sesi, pos yang dicapai, dan titik jejaknya tetap ada di basis data. Varian batal tetap tidak
     * menampilkan pos terjauh, peta jejak, atau angka; sesiDimulai memilih kalimat cabang ini.
     */
    public function test_a_trip_cancelled_after_the_hike_started_shows_no_furthest_post_map_or_numbers(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Jalur Batal Tengah']);
        foreach (range(1, 3) as $urutan) {
            Checkpoint::factory()->for($trail)->create(['sequence' => $urutan, 'name' => 'Pos '.$urutan]);
        }
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::CANCELLED->value, 'cancellation_reason' => CancellationReason::WEATHER->value]);
        $sesi = HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => Carbon::parse('2026-09-19 22:00:00', 'UTC'), 'reached_checkpoint_sequence' => 2]);
        foreach ([0, 1, 2] as $i) {
            HikeTrackPoint::create(['hiking_session_id' => $sesi->id, 'recorded_at' => Carbon::parse('2026-09-19 22:10:00', 'UTC')->addMinutes($i * 5), 'latitude' => -7.45 - $i * 0.001, 'longitude' => 110.44, 'elevation_m' => 1500]);
        }

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Trip Jalur Batal Tengah dibatalkan')
            ->where('varian', 'batal')
            ->where('sesiDimulai', true)
            ->where('posTerjauh', null)
            ->where('jejak', null)
            ->where('angka', null));
    }

    /**
     * Ruling W6-6f (M-1): kalimat varian batal bercabang menurut sesiDimulai, persis teks ruling,
     * dengan cabang "sudah dimulai" pada sesiDimulai benar. Kalimat tambahan "Keputusan itu
     * tercatat apa adanya di riwayat Anda." dibuang (m-1): trip batal tanpa riwayat tidak ada di
     * Riwayat Pendakian maupun Progres.
     */
    public function test_the_cancelled_sentence_follows_whether_the_hike_started(): void
    {
        $sumber = File::get(resource_path('js/pages/Community/HikeSummary.tsx'));

        $this->assertMatchesRegularExpression(
            "/\\{sesiDimulai\\s*\\?\\s*'Pendakian ini dihentikan sesudah dimulai lalu dibatalkan, jadi jarak, waktu, dan pos yang dicapai tidak dihitung\\.'\\s*:\\s*'Trip ini dibatalkan sebelum pendakian dimulai\\.'\\s*\\}/",
            $sumber,
        );
        $this->assertStringNotContainsString('Keputusan itu tercatat apa adanya di riwayat Anda.', $sumber);
    }

    /**
     * Ruling W6-6i (M-4): Jam dan Menit hanya bilangan bulat. Penjaganya memeriksa keduanya
     * SEBELUM form.put, menampilkan kalimat galat, memindah fokus ke Jam, dan berhenti tanpa
     * mengirim; "8,5" tidak pernah menjadi null diam-diam (R-007). Kosong tetap diizinkan.
     */
    public function test_hours_and_minutes_are_checked_as_whole_numbers_before_sending(): void
    {
        $sumber = File::get(resource_path('js/components/community/AngkaPendakian.tsx'));

        $this->assertStringContainsString("const bulat = (s: string) => s === '' || /^\\d+$/.test(s);", $sumber);

        $penjaga = strpos($sumber, 'if (!bulat(form.data.jam) || !bulat(form.data.menit)) {');
        $kirim = strpos($sumber, 'form.put(angka.ubah');
        $this->assertNotFalse($penjaga);
        $this->assertNotFalse($kirim);
        $this->assertLessThan($kirim, $penjaga);

        $badan = substr($sumber, $penjaga, strpos($sumber, 'return;', $penjaga) - $penjaga);
        $this->assertStringContainsString("setGalatWaktuKlien('Isi dengan bilangan bulat, misalnya 8.');", $badan);
        $this->assertStringContainsString("fokusKe('angka-jam');", $badan);
    }

    /**
     * Ruling W6-6e (I-3), penangan galat Ubah angka: fokus pindah ke isian galat pertama menurut
     * urutan isian, waktu_menit ke Jam. Jam dan Menit membawa aria-invalid dan satu wilayah galat
     * bersama lewat aria-describedby, jadi galatnya dibacakan saat fokus tiba.
     */
    public function test_a_refused_number_moves_focus_to_the_first_field_in_error(): void
    {
        $sumber = File::get(resource_path('js/components/community/AngkaPendakian.tsx'));
        $mulai = strpos($sumber, 'onError: (errors) => {');
        $this->assertNotFalse($mulai);
        $badan = substr($sumber, $mulai, strpos($sumber, '});', $mulai) - $mulai);

        $this->assertStringContainsString("['waktu_menit', 'angka-jam'],", $badan);
        $this->assertStringContainsString("fokusKe(urutan.find(([kunci]) => Boolean(errors[kunci]))?.[1] ?? 'angka-jarak');", $badan);

        foreach (['Jam', 'Menit'] as $label) {
            $awal = strpos($sumber, 'label="'.$label.'"');
            $this->assertNotFalse($awal, $label);
            $isian = substr($sumber, $awal, strpos($sumber, '/>', $awal) - $awal);
            $this->assertStringContainsString('invalid={Boolean(galatWaktu)}', $isian, $label);
            $this->assertStringContainsString("describedBy={galatWaktu ? 'angka-waktu-galat' : undefined}", $isian, $label);
        }

        $this->assertStringContainsString('aria-invalid={galat || invalid ? true : undefined}', $sumber);
    }

    /**
     * Ruling W6-6h: di bawah 400 px kolom angka hanya sekitar 112 px, dan pada text-judul (28 px)
     * "↑1.650 m" terpatah walau diikat U+00A0. Angka memakai text-judul-kartu di bawah 400 px,
     * pola AngkaNaik halaman jalur. Bukti lebarnya di peramban (laporan putaran perbaikan 2).
     */
    public function test_the_numbers_use_a_smaller_type_step_on_narrow_screens(): void
    {
        // Upgrade tailwind 4: break-words bernama wrap-break-word, [overflow-wrap:anywhere] bernama wrap-anywhere; CSS-nya sama.
        $this->assertStringContainsString(
            "'mt-1 wrap-break-word text-judul-kartu tabular-nums min-[400px]:text-judul'",
            File::get(resource_path('js/components/community/AngkaPendakian.tsx')),
        );
    }

    public function test_the_duration_and_furthest_post_still_read_from_the_session(): void
    {
        [$user, $trip] = $this->pendakianSelesai(posTercapai: 3);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('posTerjauh', 'Pos 3')
            ->where('angka.waktu', "8\u{a0}jam"));
    }

    /**
     * Keadaan yang umum, bukan pinggiran: banyak pendaki tidak pernah membuka mode lapangan.
     * Ringkasannya tetap ada; waktu bergerak dan pos terjauh tidak tercatat.
     */
    public function test_a_trip_that_never_used_field_mode_still_has_a_summary(): void
    {
        [$user, $trip] = $this->pendakianSelesai(denganSesi: false, posTercapai: null);

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.waktu', null)
            ->where('posTerjauh', null)
            ->where('jejak', null));
    }

    public function test_another_hiker_cannot_read_it(): void
    {
        [, $trip] = $this->pendakianSelesai();

        $this->actingAs(User::factory()->create())
            ->get(route('history.summary', $trip))
            ->assertForbidden();
    }

    /**
     * Trip yang belum selesai dan tidak dibatalkan belum punya hasil untuk diringkas.
     */
    public function test_a_trip_that_is_not_finished_has_nothing_to_summarise(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->history()->delete();
        $trip->update(['status' => TripStatus::PLANNED->value]);

        $this->buka($user, $trip->fresh())->assertNotFound();
    }

    /**
     * Profil elevasi dan peta pos dipakai ulang: server mengirim titiknya, komponen bersama
     * yang menggambarnya.
     */
    public function test_it_draws_the_trail_with_the_shared_components(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->trail->update([
            'elevation_profile' => [
                ['km' => 0.0, 'm' => 1200],
                ['km' => 2.0, 'm' => 1800],
                ['km' => 4.0, 'm' => 2400],
            ],
        ]);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('profil.titik', 3)
            ->where('angka.tertinggi', "2.400\u{a0}m")
            ->has('penandaPos', 4)
            ->where('penandaPos.0.label', '1. Pos 1')
            ->has('pos', 4)
            ->where('pos.1.antara', fn (?string $antara) => str_contains((string) $antara, 'naik 200 m')));

        $sumber = File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
        $this->assertStringContainsString('<ProfilElevasi profil={profil} />', $sumber);
        $this->assertStringContainsString('<DaftarPos pos={pos} />', $sumber);
        $this->assertStringContainsString('<PetaGaris', $sumber);
    }
}
