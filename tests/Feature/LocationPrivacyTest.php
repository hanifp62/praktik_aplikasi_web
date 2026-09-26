<?php

namespace Tests\Feature;

use App\Models\RecommendationResult;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * PRD §120 and BR-13: geolocation is only requested inside Hike Mode, and precise location
 * is never serialised out of the model.
 */
class LocationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_browsing_trails_does_not_request_geolocation(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create();

        $this->actingAs($user)
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertDontSee('navigator.geolocation', escape: false);

        // Halaman jalur kini React: yang dapat meminta lokasi adalah kodenya sendiri.
        foreach ([resource_path('js/pages/Trails'), resource_path('js/components/trails')] as $folder) {
            foreach (File::allFiles($folder) as $berkas) {
                $this->assertStringNotContainsString('geolocation', $berkas->getContents(), $berkas->getRelativePathname());
            }
        }
    }

    public function test_hike_mode_is_where_geolocation_is_requested(): void
    {
        $trip = TripPlan::factory()->create();

        // Mode pendakian kini React: permintaan lokasinya ada di hook yang hanya
        // dipakai halaman Trips/Hike.
        $this->actingAs($trip->user)
            ->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike'));

        $this->assertStringContainsString('navigator.geolocation', File::get(resource_path('js/components/trips/usePendakian.ts')));
        $this->assertStringContainsString('usePosisiPendakian', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }

    /**
     * Aturan keenam ARCHITECTURE.md: tidak ada kode peramban lain yang meminta lokasi,
     * selain hook mode pendakian dan perekam jejak yang hanya dimuatnya.
     */
    public function test_no_other_client_code_requests_geolocation(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (in_array($nama, ['components/trips/usePendakian.ts', 'jejak.js'], true)) {
                continue;
            }

            if (str_contains($berkas->getContents(), 'navigator.geolocation')) {
                $pelanggar[] = $nama;
            }
        }

        $this->assertSame([], $pelanggar, 'Lokasi diminta di luar mode pendakian: '.implode(', ', $pelanggar));
    }

    public function test_hiking_session_location_is_hidden_from_serialisation(): void
    {
        $trip = TripPlan::factory()->create();
        $session = $trip->hikingSession()->create([
            'user_id' => $trip->user_id,
            'status' => 'ACTIVE',
            'started_at' => now(),
        ]);

        $this->assertArrayNotHasKey('last_known_location', $session->fresh()->toArray());
    }

    public function test_internal_fit_score_is_never_serialised_to_the_client(): void
    {
        $result = new RecommendationResult([
            'internal_score' => 82.4,
            'label' => 'COCOK',
        ]);

        $this->assertArrayNotHasKey('internal_score', $result->toArray());
    }
}
