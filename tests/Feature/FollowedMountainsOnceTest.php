<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Gunung yang diikuti dibaca sekali per muat (titipan W5-4a T-2 untuk W9).
 *
 * KabarJalur memuat gunung yang diikuti untuk judul dan pintu ke halaman gunung, lalu
 * TrailNewsService membaca mountain_follows lagi untuk cakupan kabarnya. Kuerinya tetap (tidak
 * tumbuh bersama data), jadi PageQueryBudgetTest tidak menangkapnya; yang ditutup di sini adalah
 * pembacaan ganda di Beranda dan halaman Kabar.
 */
class FollowedMountainsOnceTest extends TestCase
{
    use RefreshDatabase;

    private function pendakiYangMengikuti(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->for($gunung)->easy()->published()->create();
        TrailConditionReport::factory()->approved()->for($jalur)->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        return $user;
    }

    private function bacaanIkuti(string $url): int
    {
        $this->get($url)->assertOk();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(array_filter(DB::getQueryLog(), fn (array $kueri): bool => str_contains($kueri['query'], 'mountain_follows')));
        DB::disableQueryLog();

        return $jumlah;
    }

    public function test_the_home_reads_the_followed_mountains_once(): void
    {
        $this->actingAs($this->pendakiYangMengikuti());

        $this->assertSame(1, $this->bacaanIkuti(route('dashboard')));
    }

    public function test_the_news_page_reads_the_followed_mountains_once(): void
    {
        $this->actingAs($this->pendakiYangMengikuti());

        $this->assertSame(1, $this->bacaanIkuti(route('news')));
    }
}
