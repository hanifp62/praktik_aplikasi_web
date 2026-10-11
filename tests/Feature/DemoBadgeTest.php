<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Lencana "Data contoh" (DK-35, antislop R-38): data contoh tidak pernah terbaca sebagai
 * data sungguhan. Mode ini mati kecuali diminta lewat APP_DEMO.
 */
class DemoBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_badge_prop_follows_the_demo_setting(): void
    {
        $budi = User::factory()->create();
        $budi->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);

        config(['app.demo' => false]);
        $this->actingAs($budi)->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('demo', false));

        config(['app.demo' => true]);
        $this->actingAs($budi)->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('demo', true));
    }

    public function test_every_app_layout_mounts_the_badge(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx'] as $berkas) {
            $isi = File::get(resource_path($berkas));

            $this->assertStringContainsString("import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';", $isi, $berkas);
            $this->assertStringContainsString('<LencanaDataContoh />', $isi, $berkas);
        }

        $lencana = File::get(resource_path('js/components/ui/LencanaDataContoh.tsx'));

        $this->assertMatchesRegularExpression('/if \(!demo\) \{\s*return null;\s*\}/', $lencana);
        $this->assertStringContainsString('<Lencana nada="netral" garis="putus">', $lencana);
        $this->assertStringContainsString('<span className="sr-only">: isi halaman ini bukan data sungguhan</span>', $lencana);
    }

    public function test_the_setting_is_off_unless_asked_for(): void
    {
        $this->assertMatchesRegularExpression('/^APP_DEMO=false$/m', File::get(base_path('.env.example')));
        $this->assertStringContainsString("'demo' => (bool) env('APP_DEMO', false),", File::get(config_path('app.php')));
    }
}
