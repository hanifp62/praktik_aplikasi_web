<?php

namespace App\Http\Controllers\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\CancelTripRequest;
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

        if ($penolakan = $lifecycle->refusal($trip, TripStatus::IN_PROGRESS) ?? $lifecycle->start($trip)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        return redirect()->route('trips.hike', $trip);
    }

    /**
     * complete() dan cancel() sekarang mengembalikan kalimat penolakan sendiri (ruling
     * W6-5g/K-2 putaran perbaikan 1): refusal() di bawah membaca $trip yang dimuat di awal
     * permintaan dan bisa basi, sedangkan UPDATE bersyarat di dalam kedua metode lifecycle
     * adalah penjaga yang sebenarnya. Pola `??` sama dengan start() di atas: refusal() dicek
     * dulu untuk kalimat yang sudah dikenal (transisi biasa yang tidak sah), dan lifecycle
     * hanya dipanggil bila refusal() tidak menemukan masalah.
     */
    public function complete(CompleteTripRequest $request, TripPlan $trip, TripLifecycle $lifecycle): Response
    {
        if ($penolakan = $lifecycle->refusal($trip, TripStatus::COMPLETED) ?? $lifecycle->complete(
            $trip,
            CompletionState::from($request->validated('completion_state')),
            $request->validated('personal_notes'),
            $request->user(),
            CancellationReason::tryFrom((string) $request->validated('alasan')),
        )) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        // Ringkasan memuat formulir laporan kondisi (spec v3 bagian 5.3 dan 6.13).
        return redirect()->route('history.summary', $trip);
    }

    public function cancel(CancelTripRequest $request, TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        if ($penolakan = $lifecycle->refusal($trip, TripStatus::CANCELLED) ?? $lifecycle->cancel($trip, CancellationReason::tryFrom((string) $request->validated('alasan')))) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        return redirect()->route('trips.show', $trip)->with('status', 'Trip dibatalkan.');
    }
}
