<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Livewire\Feed\TrailNews;
use App\Livewire\Trails\TrailDetail;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\TrailNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kabar jalur.
 *
 * Satu-satunya Trigger dalam kerangka Fogg di aplikasi ini: seluruh layar lain melayani
 * seseorang yang sudah memutuskan merencanakan pendakian, sedangkan ini memberi alasan
 * membukanya di antara dua pendakian.
 *
 * Isinya keadaan gunung, bukan aktivitas orang. Umpan Strava berisi siapa mendaki apa;
 * umpan ini berisi apa yang berubah di gunung yang Anda ikuti.
 */
class TrailNewsTest extends TestCase
{
    use RefreshDatabase;

    private function gunungBerjalur(string $nama): array
    {
        $gunung = Mountain::factory()->create(['name' => $nama]);

        return [$gunung, Trail::factory()->easy()->for($gunung)->create(['name' => 'Jalur '.$nama])];
    }

    private function tutup(Trail $trail, string $alasan): OfficialStatus
    {
        return OfficialStatus::create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Besar TN',
            'effective_at' => now()->subDay(),
            'reason' => $alasan,
        ]);
    }

    private function laporan(Trail $trail, ModerationStatus $status, string $catatan = 'Jembatan Pos 3 putus.'): TrailConditionReport
    {
        return TrailConditionReport::factory()->for($trail)->create([
            'user_id' => User::factory()->create()->id,
            'moderation_status' => $status->value,
            'note' => $catatan,
        ]);
    }

    private function kabar(User $user)
    {
        return app(TrailNewsService::class)->forUser($user);
    }

    public function test_a_hiker_can_follow_and_unfollow_a_mountain(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(TrailDetail::class, ['trail' => $trail])
            ->call('ikutiGunung');

        $this->assertTrue(app(TrailNewsService::class)->follows($user, $gunung));

        Livewire::actingAs($user->fresh())->test(TrailDetail::class, ['trail' => $trail])
            ->call('ikutiGunung', false);

        $this->assertFalse(app(TrailNewsService::class)->follows($user->fresh(), $gunung));
    }

    public function test_the_feed_carries_status_changes_and_reports_from_followed_mountains(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);

        $kabar = $this->kabar($user);

        $this->assertCount(2, $kabar);
        // Diurutkan abjad sebelum dibandingkan supaya test ini tidak ikut menguji
        // urutan waktunya; urutan itu punya testnya sendiri.
        $this->assertSame(
            [TrailNewsService::LAPORAN, TrailNewsService::STATUS],
            $kabar->pluck('jenis')->sort()->values()->all()
        );
    }

    /**
     * Gunung yang tidak diikuti tidak boleh masuk. Umpan yang memuat segalanya berhenti
     * menjadi alasan membuka aplikasi dan menjadi alasan mematikan pemberitahuan.
     */
    public function test_an_unfollowed_mountain_never_appears(): void
    {
        [$diikuti, $jalurDiikuti] = $this->gunungBerjalur('Merbabu');
        [, $jalurLain] = $this->gunungBerjalur('Semeru');

        $user = User::factory()->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $diikuti->id]);

        $this->tutup($jalurDiikuti, 'Kebakaran lahan.');
        $this->tutup($jalurLain, 'Aktivitas vulkanik.');

        $kabar = $this->kabar($user);

        $this->assertCount(1, $kabar);
        $this->assertSame('Jalur Merbabu', $kabar[0]['judul']);
    }

    /**
     * Kabar adalah pintu yang paling ramai, dan laporan yang belum diperiksa bocor lewat
     * sini akan menghapus seluruh guna moderasinya.
     */
    public function test_reports_awaiting_or_refused_by_moderation_never_appear(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        $this->laporan($trail, ModerationStatus::PENDING, 'Belum diperiksa.');
        $this->laporan($trail, ModerationStatus::REJECTED, 'Ditolak.');

        $this->assertCount(0, $this->kabar($user));
    }

    /**
     * Penutupan diumumkan untuk kawasan, bukan hanya untuk jalur, dan pendaki yang
     * mengikuti Merbabu perlu tahu ketika seluruh Merbabu ditutup.
     */
    public function test_a_closure_announced_for_the_whole_mountain_reaches_the_feed(): void
    {
        [$gunung] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        OfficialStatus::create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Besar TN',
            'effective_at' => now(),
            'reason' => 'Aktivitas vulkanik meningkat.',
        ]);

        $kabar = $this->kabar($user);

        $this->assertCount(1, $kabar);
        $this->assertSame('Merbabu', $kabar[0]['judul']);
    }

    public function test_the_feed_is_ordered_newest_first(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        $lama = $this->laporan($trail, ModerationStatus::APPROVED, 'Yang lama.');
        $lama->update(['created_at' => now()->subDays(5)]);

        $this->tutup($trail, 'Yang baru.');

        $this->assertSame(TrailNewsService::STATUS, $this->kabar($user)[0]['jenis']);
    }

    /**
     * Pendaki yang belum mengikuti gunung mana pun tidak diberi layar kosong, melainkan
     * ajakan memilih beserta alasannya.
     */
    public function test_someone_following_nothing_is_invited_to_choose(): void
    {
        $user = User::factory()->create();
        $this->gunungBerjalur('Merbabu');

        $this->actingAs($user)
            ->get(route('news'))
            ->assertOk()
            ->assertSee('Belum ada gunung yang Anda ikuti');
    }

    /**
     * PRD §92: keterangan resmi dan masukan komunitas tidak pernah tertukar, termasuk
     * ketika keduanya berbagi satu urutan waktu.
     */
    public function test_official_and_community_entries_stay_visually_distinct(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);

        $halaman = Livewire::actingAs($user)->test(TrailNews::class);

        $halaman->assertSee('Status resmi');
        $halaman->assertSee('Laporan komunitas');
        $halaman->assertSee('border-l-community-500', escape: false);
    }
}
