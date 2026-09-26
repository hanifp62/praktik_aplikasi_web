<?php

namespace App\Http\Controllers\Trips;

use App\Enums\CompletionState;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\CompleteTripRequest;
use App\Models\TripPlan;
use App\Services\Trips\TripLifecycle;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tiga aksi pada halaman trip: mulai hike mode, selesaikan, batalkan. Dahulu metode
 * komponen TripShow; Policy dan penjaga transisinya sama.
 *
 * Penolakan transisi dikirim sebagai flash tersendiri, bukan lewat 'status': halaman
 * merender 'status' sebagai kotak berhasil, dan kotak berhasil yang berisi kabar gagal
 * adalah pesan yang membantah dirinya sendiri.
 */
class TripLifecycleController extends Controller
{
    public function start(TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($penolakan = $lifecycle->refusal($trip, TripStatus::IN_PROGRESS)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $lifecycle->start($trip);

        return redirect()->route('trips.hike', $trip);
    }

    public function complete(CompleteTripRequest $request, TripPlan $trip, TripLifecycle $lifecycle): Response
    {
        if ($penolakan = $lifecycle->refusal($trip, TripStatus::COMPLETED)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $lifecycle->complete(
            $trip,
            CompletionState::from($request->validated('completion_state')),
            $request->validated('personal_notes'),
            $request->user(),
        );

        return redirect()->route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id]);
    }

    public function cancel(TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($penolakan = $lifecycle->refusal($trip, TripStatus::CANCELLED)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $lifecycle->cancel($trip);

        return redirect()->route('trips.show', $trip)->with('status', 'Trip dibatalkan.');
    }
}
