<?php

namespace Tests\Feature;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\User;
use App\Models\VolcanoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman Kabar sesudah redesain W8 (W5-C9; ruling W8-R27): pintu ke setiap gunung yang diikuti,
 * dan kelompok status gunung api PVMBG yang hanya tampil untuk gunung yang diikuti dan sudah punya
 * catatan (DK-43), terpisah dari status akses.
 */
class KabarPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_followed_mountains_link_to_their_pages(): void
    {
        $user = User::factory()->create();
        $gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gede->id]);

        $this->actingAs($user)->get(route('news'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('gunung', 1)
            ->where('gunung.0.nama', 'Gunung Gede')
            ->where('gunung.0.url', route('mountains.show', $gede))
            ->where('gunungApi', []));
    }

    public function test_the_volcano_group_lists_only_followed_mountains_with_a_record(): void
    {
        $user = User::factory()->create();
        $merapi = Mountain::factory()->create(['name' => 'Gunung Merapi']);
        $gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        $lain = Mountain::factory()->create(['name' => 'Gunung Lain']);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $merapi->id]);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gede->id]);
        VolcanoStatus::factory()->for($merapi)->create(['level' => VolcanoLevel::SIAGA->value]);
        VolcanoStatus::factory()->for($lain)->create(['level' => VolcanoLevel::AWAS->value]);

        $this->actingAs($user)->get(route('news'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('gunungApi', 1)
            ->where('gunungApi.0.gunung', 'Gunung Merapi')
            ->where('gunungApi.0.label', 'Level III (Siaga)')
            ->where('gunungApi.0.atribusi', VolcanoLevel::ATRIBUSI));
    }

    public function test_following_nothing_sends_empty_groups(): void
    {
        $this->actingAs(User::factory()->create())->get(route('news'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('gunung', [])
            ->where('gunungApi', [])
            ->where('mengikuti', null));
    }
}
