<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tangga tanjakan Beranda (spec v3 bagian 6.1, prototype 01, rencana induk W5 butir 5): tanjakan
 * jalur yang pernah dituntaskan, dari yang terendah, dengan trip mendatang sebagai anak tangga
 * rencana. Diukur dari elevation gain, bukan ketinggian puncak (BR-02), dan dibandingkan dengan
 * pendakian pendakinya sendiri (R-015).
 */
class DashboardLadderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function pendaki(bool $profil = true): User
    {
        $user = User::factory()->create();

        if ($profil) {
            $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        }

        return $user->fresh();
    }

    private function tuntas(User $user, string $nama, ?int $tanjakan, CompletionState $hasil = CompletionState::COMPLETED, ?Trail $jalur = null): Trail
    {
        $jalur ??= Trail::factory()->create(['name' => $nama, 'elevation_gain_m' => $tanjakan]);
        $trip = TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => '2026-08-01', 'status' => TripStatus::COMPLETED->value, 'completed_at' => now()->subDays(50)]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'completed_at' => now()->subDays(50),
        ]);

        return $jalur;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tangga(User $user): ?array
    {
        return $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['tangga'];
    }

    public function test_the_ladder_climbs_from_the_lowest_completed_gain_to_the_planned_trip(): void
    {
        $user = $this->pendaki();
        $prau = $this->tuntas($user, 'Prau via Wates', 700);
        $andong = $this->tuntas($user, 'Andong via Sawit', 380);
        $batur = $this->tuntas($user, 'Batur via Toya Bungkah', 600);
        $gede = Trail::factory()->published()->create(['name' => 'Gede via Cibodas', 'elevation_gain_m' => 1650]);
        TripPlan::factory()->for($user)->for($gede)->create(['planned_date' => '2026-10-10', 'status' => TripStatus::PLANNED->value]);

        $tangga = $this->tangga($user);

        $this->assertSame([
            [$andong->id, 'Andong via Sawit', $andong->mountain->name, '380 m', false],
            [$batur->id, 'Batur via Toya Bungkah', $batur->mountain->name, '600 m', false],
            [$prau->id, 'Prau via Wates', $prau->mountain->name, '700 m', false],
            [$gede->id, 'Gede via Cibodas', $gede->mountain->name, '1.650 m', true],
        ], array_map(fn (array $a) => [$a['id'], $a['jalur'], $a['gunung'], $a['teks'], $a['rencana']], $tangga['anakTangga']));
        $this->assertSame(
            'Gede via Cibodas: jauh di atas tanjakan terberat yang pernah Anda tuntaskan. Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.',
            $tangga['kalimat'],
        );
    }

    /**
     * Pendakian yang tidak tuntas tidak membuktikan tanjakannya dan tidak menjadi anak tangga;
     * jalur yang didaki dua kali tetap satu anak tangga, dan yang tampil paling banyak empat
     * tanjakan tertinggi. Jalur tanpa angka tanjakan disertakan di fixture ini hanya sebagai
     * bagian skenario dedup/batas -- di sini ia sudah tersingkir oleh LIMIT sebelum
     * whereNotNull sempat berperan, jadi tidak membuktikan R-007 sendirian. Perlindungan R-007
     * terisolasi dari LIMIT diuji di test_a_trail_without_a_known_gain_is_excluded_not_zeroed
     * (ruling W5-6d).
     */
    public function test_only_completed_hikes_with_a_known_gain_become_rungs_once_per_trail(): void
    {
        $user = $this->pendaki();
        $prau = $this->tuntas($user, 'Prau via Wates', 700);
        $this->tuntas($user, 'Prau via Wates', 700, jalur: $prau);
        $this->tuntas($user, 'Sindoro via Kledung', 1500, CompletionState::ABANDONED);
        $this->tuntas($user, 'Jalur tanpa data', null);

        foreach ([['Andong via Sawit', 380], ['Batur via Toya Bungkah', 600], ['Merbabu via Selo', 1100], ['Papandayan via Camp David', 610]] as [$nama, $meter]) {
            $this->tuntas($user, $nama, $meter);
        }

        $tangga = $this->tangga($user);

        $this->assertSame(['Batur via Toya Bungkah', 'Papandayan via Camp David', 'Prau via Wates', 'Merbabu via Selo'], array_column($tangga['anakTangga'], 'jalur'));
        $this->assertSame('Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.', $tangga['kalimat']);
    }

    /**
     * Tanpa pendakian tuntas tidak ada tangga; sebelum profil terisi dan selama mendaki juga tidak.
     */
    public function test_the_ladder_needs_a_completed_hike_and_stays_out_before_a_profile_and_while_hiking(): void
    {
        $this->assertNull($this->tangga($this->pendaki()));

        $tanpaProfil = $this->pendaki(profil: false);
        $this->tuntas($tanpaProfil, 'Prau via Wates', 700);
        $this->assertNull($this->tangga($tanpaProfil));

        $mendaki = $this->pendaki();
        $this->tuntas($mendaki, 'Prau via Wates', 700);
        $trip = TripPlan::factory()->for($mendaki)->create(['planned_date' => '2026-09-25', 'status' => TripStatus::IN_PROGRESS->value]);
        HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $mendaki->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);
        $this->assertNull($this->tangga($mendaki));
    }

    /**
     * Satu seri tanpa legenda: setiap nilai tertulis, hanya batangnya yang disembunyikan dari
     * pembaca layar, dan anak tangga rencana dibedakan dengan kata dan garis, bukan warna saja.
     */
    public function test_the_ladder_writes_every_value_and_hides_only_the_bars(): void
    {
        $sumber = File::get(resource_path('js/components/beranda/TanggaTanjakan.tsx'));

        $this->assertSame(1, substr_count($sumber, 'aria-hidden="true"'));
        $this->assertStringContainsString('{a.teks}', $sumber);
        $this->assertStringContainsString(', rencana</span>', $sumber);
        $this->assertStringContainsString("'border-2 border-dashed border-brand-700'", $sumber);
    }

    /**
     * Pendakian baru selesai yang belum dilaporkan (B4) tetap menampilkan tangga:
     * `KonteksBeranda::$trip` (dibaca `TanggaTanjakan::untuk()`) hanya terisi pada B2
     * (`RingkasanDasbor::konteks()`), jadi B4 tidak pernah menambah anak tangga rencana dan
     * kalimatnya tetap kalimat bawaan (ruling W5-6b / I2).
     */
    public function test_the_ladder_still_shows_in_b4_with_no_planned_rung(): void
    {
        $user = $this->pendaki();
        $this->tuntas($user, 'Andong via Sawit', 380);

        $jalur = Trail::factory()->create(['name' => 'Gede via Cibodas', 'elevation_gain_m' => 1650]);
        $trip = TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => '2026-09-20', 'status' => TripStatus::COMPLETED->value, 'completed_at' => now()->subDays(2)]);
        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now()->subDays(2),
        ]);

        $halaman = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props'];

        $this->assertSame('B4', $halaman['fase']['kunci']);
        $this->assertNotNull($halaman['tangga']);
        $this->assertFalse(in_array(true, array_column($halaman['tangga']['anakTangga'], 'rencana'), true));
        $this->assertSame('Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.', $halaman['tangga']['kalimat']);
    }

    /**
     * R-007 terisolasi dari LIMIT: jalur tanpa angka tanjakan tidak digambar sebagai nol, pada
     * fixture yang jumlah riwayat tuntas lainnya di bawah ANAK_TANGGA, sehingga jalur tanpa data
     * itu punya ruang untuk masuk top-4 bila whereNotNull tidak ada (ruling W5-6d / I4).
     */
    public function test_a_trail_without_a_known_gain_is_excluded_not_zeroed(): void
    {
        $user = $this->pendaki();
        $this->tuntas($user, 'Andong via Sawit', 380);
        $this->tuntas($user, 'Batur via Toya Bungkah', 600);
        $this->tuntas($user, 'Jalur tanpa data', null);

        $tangga = $this->tangga($user);

        $this->assertCount(2, $tangga['anakTangga']);
        $this->assertSame(['Andong via Sawit', 'Batur via Toya Bungkah'], array_column($tangga['anakTangga'], 'jalur'));
        $this->assertNotContains('0 m', array_column($tangga['anakTangga'], 'teks'));
    }

    /**
     * `trails.name` tidak unik (migrasi trails: hanya slug yang unique); dua jalur boleh
     * bernama sama di dua gunung berbeda. Tangga membedakannya lewat id dan gunung, bukan nama
     * jalur saja, dan kunci React memakai id (bukan nama) persis karena alasan ini
     * (ruling W5-6a / I1).
     */
    public function test_two_trails_with_the_same_name_on_different_mountains_stay_distinct_rungs(): void
    {
        $user = $this->pendaki();
        $gunungSatu = Mountain::factory()->create(['name' => 'Gunung Satu']);
        $gunungDua = Mountain::factory()->create(['name' => 'Gunung Dua']);
        $jalurSatu = Trail::factory()->for($gunungSatu)->create(['name' => 'Jalur Utara', 'elevation_gain_m' => 500]);
        $jalurDua = Trail::factory()->for($gunungDua)->create(['name' => 'Jalur Utara', 'elevation_gain_m' => 900]);
        $this->tuntas($user, 'Jalur Utara', 500, jalur: $jalurSatu);
        $this->tuntas($user, 'Jalur Utara', 900, jalur: $jalurDua);

        $tangga = $this->tangga($user);

        $this->assertSame(['Jalur Utara', 'Jalur Utara'], array_column($tangga['anakTangga'], 'jalur'));
        $this->assertSame([
            [$jalurSatu->id, 'Gunung Satu'],
            [$jalurDua->id, 'Gunung Dua'],
        ], array_map(fn (array $a) => [$a['id'], $a['gunung']], $tangga['anakTangga']));

        $sumber = File::get(resource_path('js/components/beranda/TanggaTanjakan.tsx'));
        $this->assertStringContainsString('key={`${a.id}-', $sumber);
        $this->assertStringContainsString('<span className="sr-only">, </span>{a.gunung}', $sumber);
    }
}
