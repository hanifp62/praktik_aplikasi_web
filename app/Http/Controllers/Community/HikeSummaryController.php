<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\Community\HasilPendakian;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hasil satu pendakian. Pemilik URL bukan pemilik trip (PRD §77): policy yang sama
 * dengan komponen Livewire yang digantikannya.
 */
class HikeSummaryController extends Controller
{
    public function __invoke(TripPlan $trip, HasilPendakian $hasil): Response
    {
        $this->authorize('view', $trip);

        // Trip yang belum diselesaikan belum punya hasil untuk diringkas, dan ringkasan
        // kosong akan terbaca seolah pendakiannya sudah usai.
        abort_unless($trip->history()->exists(), 404);

        $props = $hasil->untuk($trip);

        return Inertia::render('Community/HikeSummary', [
            'title' => 'Hasil pendakian '.$props['jalur']['nama'],
            ...$props,
        ]);
    }
}
