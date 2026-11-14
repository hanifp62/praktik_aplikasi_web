<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Padanan pembaca layar untuk waktu tempuh antarpos di checkpoint-journey.blade.php
 * dan penutupan Escape pada MenuAkun.tsx (GAP-24).
 */
class CheckpointJourneyAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkpoint_journey_exposes_pace_to_screen_readers_including_when_geometry_is_missing(): void
    {
        $jalur = Trail::factory()->easy()->create();

        $pos1 = Checkpoint::factory()->for($jalur)->create([
            'sequence' => 1,
            'name' => 'Basecamp',
            'latitude' => null,
            'longitude' => null,
            'elevation_m' => null,
        ]);
        $pos2 = Checkpoint::factory()->for($jalur)->create([
            'sequence' => 2,
            'name' => 'Pos 1',
            'latitude' => null,
            'longitude' => null,
            'elevation_m' => null,
        ]);

        $html = Blade::render(
            '<x-ui.checkpoint-journey :checkpoints="$pos" :paces="$tempo" />',
            [
                'pos' => collect([$pos1, $pos2]),
                'tempo' => [
                    [
                        'dari' => $pos1->id,
                        'ke' => $pos2->id,
                        'median_menit' => 75,
                        'min_menit' => 60,
                        'maks_menit' => 95,
                        'rekaman' => 12,
                    ],
                ],
            ]
        );

        $this->assertMatchesRegularExpression(
            '/<p class="sr-only">[^<]*Waktu tempuh dari pos sebelumnya biasanya 1 jam 15 menit, terentang 1 jam sampai 1 jam 35 menit, menurut 12 rekaman pendaki\./s',
            $html
        );
    }

    public function test_account_menu_closes_on_escape_and_restores_focus_to_trigger(): void
    {
        $sumber = File::get(resource_path('js/layouts/MenuAkun.tsx'));

        $this->assertStringContainsString('popover="auto"', $sumber);
        $this->assertStringContainsString("e.key === 'Escape'", $sumber);
        $this->assertStringContainsString('pemicu.current?.focus()', $sumber);
    }
}
