<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Progres pribadi pendaki (spec v3 bagian 6.14; rencana induk W7 butir 1).
 *
 * Dalam Self-Determination Theory inilah competence: melihat kemajuan diri sendiri. Diperbarui
 * W7: angkanya kini ringkasan satu tahun, grafik per pendakian, dan riwayat dari HubProgres,
 * dibaca lewat props halaman; niat setiap test lama tetap (pendakian yang dibatalkan terhitung
 * sebagai pendakian, yang tidak diketahui tidak menjadi nol, angka milik pemiliknya, peta hanya
 * gunung berkoordinat). Angkanya dihitung tangan di test ini, bukan disalin dari keluaran kode.
 */
class HikerProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 09:00:00', 'Asia/Jakarta'));
    }

    private function riwayat(User $user, Trail $trail, string $tanggal = '2026-06-10', CompletionState $hasil = CompletionState::COMPLETED, ?CancellationReason $alasan = null): HikingHistory
    {
        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => $tanggal,
            'status' => TripStatus::COMPLETED->value,
            'cancellation_reason' => $alasan?->value,
        ]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 90,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    private function batal(User $user, Trail $trail, string $tanggal, ?CancellationReason $alasan = null): TripPlan
    {
        return TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => $tanggal,
            'status' => TripStatus::CANCELLED->value,
            'cancellation_reason' => $alasan?->value,
        ]);
    }

    private function jalur(?int $naik, ?float $km = 4.0, ?Mountain $gunung = null, string $nama = 'Jalur Uji'): Trail
    {
        return Trail::factory()->easy()->for($gunung ?? Mountain::factory()->create())->create(['name' => $nama, 'elevation_gain_m' => $naik, 'distance_km' => $km]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(User $user, array $query = []): array
    {
        return $this->actingAs($user)->get(route('progress', $query))->assertOk()->viewData('page')['props'];
    }

    public function test_the_year_summary_counts_hikes_and_sums_their_known_numbers(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, 4.0));
        $this->riwayat($user, $this->jalur(1300, 6.5), '2026-08-02');

        $ringkasan = $this->props($user)['ringkasan'];

        $this->assertSame('Pendakian 2026', $ringkasan['judul']);
        $this->assertSame('2', $ringkasan['pendakian']);
        $this->assertSame('2 selesai', $ringkasan['rincian']);
        $this->assertSame('2.200', $ringkasan['tanjakan']);
        $this->assertSame('21,0', $ringkasan['jarak'], 'Jarak data jalur pulang-pergi: 2 x 4,0 + 2 x 6,5 km.');
        $this->assertNull($ringkasan['tanjakanCatatan']);
    }

    /**
     * Membatalkan karena cuaca adalah keputusan yang benar: tetap terhitung sebagai pendakian,
     * sebelum berangkat maupun di jalur, tetapi tanjakannya tidak ikut dijumlahkan.
     */
    public function test_a_cancelled_hike_counts_as_a_hike_but_not_as_a_climb(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900));
        $this->riwayat($user, $this->jalur(1500, nama: 'Jalur Berbalik'), '2026-07-04', CompletionState::ABANDONED, CancellationReason::PHYSICAL_CONDITION);
        $this->batal($user, $this->jalur(1700, nama: 'Jalur Batal'), '2026-09-13', CancellationReason::WEATHER);

        $props = $this->props($user);

        $this->assertSame('3', $props['ringkasan']['pendakian']);
        $this->assertSame('1 selesai, 2 batal', $props['ringkasan']['rincian']);
        $this->assertSame('900', $props['ringkasan']['tanjakan']);
        $this->assertSame(['Dibatalkan · Cuaca', 'Dibatalkan di jalur · Kondisi tubuh', 'Selesai'], array_column($props['riwayat'], 'lencana'));
        $this->assertSame('Keputusan yang baik tetap tercatat sebagai pendakian.', $props['riwayat'][0]['kalimatBatal']);
        $this->assertSame('batal sebelum pendakian dimulai', $props['riwayat'][0]['baris']);
    }

    /**
     * PRD §95: angka yang belum diketahui tidak menaikkan total dan tidak dihitung nol; jumlah
     * pendakian tanpa angka disebut supaya total tidak terbaca lengkap.
     */
    public function test_a_hike_without_numbers_is_admitted_not_counted_as_zero(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, 4.0));
        $this->riwayat($user, $this->jalur(null, null), '2026-07-01');

        $ringkasan = $this->props($user)['ringkasan'];

        $this->assertSame('900', $ringkasan['tanjakan']);
        $this->assertSame('1 pendakian tanpa angka tanjakan', $ringkasan['tanjakanCatatan']);
        $this->assertSame('8,0', $ringkasan['jarak']);
        $this->assertSame('1 pendakian tanpa angka jarak', $ringkasan['jarakCatatan']);
    }

    /**
     * Angka yang ditulis pendaki sendiri ("Ubah angka" atau catatan manual) menggantikan data
     * jalur (ruling W7-R4).
     */
    public function test_numbers_the_hiker_wrote_replace_trail_data(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(1650, 9.7))->update(['recorded_gain_m' => 1200, 'recorded_distance_m' => 18000, 'numbers_edited_at' => now()]);
        HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'recorded_gain_m' => 1300, 'recorded_distance_m' => null, 'completed_at' => Carbon::parse('2026-05-23 05:00:00')]);

        $props = $this->props($user);

        $this->assertSame('2.500', $props['ringkasan']['tanjakan']);
        $this->assertSame('18,0', $props['ringkasan']['jarak']);
        $this->assertSame('1 pendakian tanpa angka jarak', $props['ringkasan']['jarakCatatan']);
        $this->assertSame(['teks' => 'Dicatat manual', 'nada' => 'redup'], $props['riwayat'][1]['catatan']);
    }

    /**
     * Progres dan ringkasan satu pendakian memakai aturan angka yang sama untuk pendakian tanpa
     * rekaman jejak (ruling W6-R13 dan W7-R4): jarak pulang-pergi dan tanjakan jalur.
     */
    public function test_progress_and_the_hike_summary_agree_on_a_hike_without_a_track(): void
    {
        $user = User::factory()->create();
        $riwayat = $this->riwayat($user, $this->jalur(1650, 9.7, nama: 'Gede via Cibodas'));

        $ringkasanPendakian = $this->actingAs($user)->get(route('history.summary', $riwayat->trip_plan_id))->assertOk()->viewData('page')['props']['angka'];
        $baris = $this->props($user)['riwayat'][0]['baris'];

        $this->assertSame("↑1.650\u{00A0}m", $ringkasanPendakian['naik']);
        $this->assertSame("19,4\u{00A0}km", $ringkasanPendakian['jarak']);
        $this->assertStringContainsString('↑1.650 m · 19,4 km', $baris);
    }

    public function test_the_numbers_belong_to_their_owner_alone(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();

        $this->riwayat($saya, $this->jalur(900));
        $this->riwayat($orangLain, $this->jalur(2000));
        HikingHistory::factory()->manual()->for($orangLain)->create(['trail_id' => null, 'trail_name' => 'Rinjani', 'recorded_gain_m' => 2600, 'completed_at' => now()->subMonth()]);

        $props = $this->props($saya);

        $this->assertSame('1', $props['ringkasan']['pendakian']);
        $this->assertSame('900', $props['ringkasan']['tanjakan']);
        $this->assertCount(1, $props['riwayat']);
    }

    public function test_a_hiker_who_has_never_hiked_sees_the_first_time_state_rather_than_zeroes(): void
    {
        $props = $this->props(User::factory()->create());

        $this->assertTrue($props['kosong']);
        $this->assertNull($props['ringkasan'], 'Belum pernah mendaki bukan berarti tanjakannya nol.');
        $this->assertNull($props['grafik']);
        $this->assertSame([], $props['riwayat']);
        $this->assertSame(['dipilih' => 2026, 'semua' => [2026]], $props['tahun']);
    }

    /**
     * Tahun dibaca dari catatan pendaki sendiri; tahun di luar rentangnya kembali ke tahun ini.
     */
    public function test_the_year_is_chosen_from_the_hikers_own_records(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, nama: 'Jalur 2025'), '2025-03-01');
        $this->riwayat($user, $this->jalur(700, nama: 'Jalur 2026'), '2026-03-01');

        $this->assertSame(['dipilih' => 2026, 'semua' => [2026, 2025]], $this->props($user)['tahun']);
        $this->assertSame(['Jalur 2025'], array_column($this->props($user, ['tahun' => 2025])['riwayat'], 'nama'));
        $this->assertSame(2026, $this->props($user, ['tahun' => 1990])['tahun']['dipilih']);
    }

    /**
     * Grafik dari pendakian paling awal; yang dibatalkan dan yang tanpa angka menyebut dirinya
     * dengan kata, dan keterangan kalimatnya menyebut nilai pertama dan terbaru tanpa menilai.
     */
    public function test_the_chart_is_chronological_and_names_what_is_unknown(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, nama: 'Jalur A'), '2026-01-15');
        $this->riwayat($user, $this->jalur(null, nama: 'Jalur B'), '2026-03-15');
        $this->batal($user, $this->jalur(1200, nama: 'Jalur C'), '2026-04-12', CancellationReason::WEATHER);
        $this->riwayat($user, $this->jalur(1300, nama: 'Jalur D'), '2026-06-20');

        $grafik = $this->props($user)['grafik'];

        $this->assertSame(['Jalur A', 'Jalur B', 'Jalur C', 'Jalur D'], array_column($grafik['batang'], 'nama'));
        $this->assertSame(['900 m', 'belum ada', 'batal', '1.300 m'], array_column(array_column($grafik['batang'], 'tanjakan'), 'teks'));
        $this->assertSame([900, null, null, 1300], array_column(array_column($grafik['batang'], 'tanjakan'), 'nilai'));
        $this->assertSame([false, false, false, true], array_column($grafik['batang'], 'terbaru'));
        $this->assertSame(
            'Tanjakan naik dari 900 m (Jalur A, Jan) ke 1.300 m (Jalur D, Jun). Jalur C di April dibatalkan karena cuaca dan tetap tercatat. 1 pendakian belum punya angka tanjakan.',
            $grafik['keterangan']['tanjakan'],
        );
    }

    public function test_the_chart_keeps_only_the_latest_twelve_hikes(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 14) as $i) {
            $this->riwayat($user, $this->jalur($i * 100, nama: 'Jalur '.$i), Carbon::parse('2026-01-01')->addDays($i * 7)->toDateString());
        }

        $grafik = $this->props($user)['grafik'];

        $this->assertCount(12, $grafik['batang']);
        $this->assertSame('Jalur 3', $grafik['batang'][0]['nama'], 'Yang paling lama terpotong, urutannya tetap dari yang awal.');
        $this->assertSame('12 pendakian terakhir di 2026 dari 14; seluruhnya ada di riwayat di bawah.', $grafik['catatan']);
    }

    /**
     * Pencarian jalur katalog untuk catatan manual hanya dikirim lewat muat ulang parsial: hanya
     * jalur terbit (R-022), paling banyak delapan, tidak peka huruf besar; kata di bawah dua
     * huruf tidak mencari apa pun.
     */
    public function test_the_catalog_search_for_a_manual_record_offers_only_published_trails(): void
    {
        $user = User::factory()->create();
        $gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        Trail::factory()->published()->easy()->for($gede)->create(['name' => 'Gede via Cibodas']);
        Trail::factory()->easy()->unpublished()->for($gede)->create(['name' => 'Gede via Selabintana']);

        foreach (range(1, 9) as $i) {
            Trail::factory()->published()->easy()->create(['name' => 'Jalur Gede Lain '.$i]);
        }

        $this->actingAs($user)->get(route('progress', ['cari' => 'GEDE']))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('pilihanJalur')
            ->reloadOnly('pilihanJalur', fn (AssertableInertia $muatUlang) => $muatUlang
                ->has('pilihanJalur', 8)
                ->where('pilihanJalur.0.nama', 'Gede via Cibodas')
                ->where('pilihanJalur.0.gunung', 'Gunung Gede')
                ->where('pilihanJalur.1.nama', 'Jalur Gede Lain 1')));

        $this->actingAs($user)->get(route('progress', ['cari' => 'g']))->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('pilihanJalur', fn (AssertableInertia $muatUlang) => $muatUlang->where('pilihanJalur', [])));
    }

    /**
     * Gunung tanpa koordinat tidak ditempatkan di tengah laut demi melengkapi peta, dan
     * pendakian yang berbalik tidak menaruh gunungnya di peta.
     */
    public function test_the_map_draws_only_mountains_with_coordinates_from_completed_hikes(): void
    {
        $user = User::factory()->create();
        $berkoordinat = Mountain::factory()->create(['name' => 'Gunung Terpetakan', 'latitude' => -7.45, 'longitude' => 110.44]);
        $tanpa = Mountain::factory()->create(['name' => 'Gunung Tanpa Titik', 'latitude' => null, 'longitude' => null]);
        $berbalik = Mountain::factory()->create(['name' => 'Gunung Berbalik', 'latitude' => -7.2, 'longitude' => 110.1]);

        $this->riwayat($user, $this->jalur(900, gunung: $berkoordinat));
        $this->riwayat($user, $this->jalur(900, gunung: $tanpa));
        $this->riwayat($user, $this->jalur(900, gunung: $berbalik), hasil: CompletionState::ABANDONED);

        $props = $this->props($user);

        $this->assertSame(['Gunung Terpetakan'], array_column($props['gunung'], 'label'));
        $this->assertSame('1 gunung lainnya belum berkoordinat, jadi belum dapat digambar.', $props['gunungTanpaKoordinat']);
    }
}
