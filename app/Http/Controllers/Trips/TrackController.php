<?php

namespace App\Http\Controllers\Trips;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pintu tab Mendaki (IA v2, DK-25).
 *
 * Pendakian yang sedang berjalan dibuka langsung: pendaki yang mengetuk tab ini di jalur
 * butuh mode lapangannya, bukan daftar. Tanpa pendakian berjalan, halaman menawarkan trip
 * yang masih dapat dimulai (direncanakan atau siap berangkat, tanggalnya belum lewat),
 * terdekat lebih dulu. Semua kueri dibatasi milik pengguna yang masuk.
 */
class TrackController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        $berjalan = TripPlan::query()
            ->whereBelongsTo($user)
            ->where('status', TripStatus::IN_PROGRESS->value)
            ->orderBy('planned_date')
            ->first();

        if ($berjalan !== null) {
            return redirect()->route('trips.hike', $berjalan);
        }

        $trips = TripPlan::query()
            ->whereBelongsTo($user)
            ->whereIn('status', [TripStatus::READY_FOR_DEPARTURE->value, TripStatus::PLANNED->value])
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->with('trail.mountain', 'latestReadinessCheck')
            ->orderBy('planned_date')
            ->orderByRaw('case when status = ? then 0 else 1 end', [TripStatus::READY_FOR_DEPARTURE->value])
            ->limit(10)
            ->get();

        return Inertia::render('Track/Index', [
            'title' => 'Mendaki',
            'trips' => $trips->map(fn (TripPlan $trip) => [
                'id' => $trip->id,
                'name' => $trip->name,
                'trail' => $trip->trail?->name,
                'mountain' => $trip->trail?->mountain?->name,
                'tanggal' => $trip->planned_date->translatedFormat('l, d F Y'),
                'status' => $trip->status->label(),
                'siap' => $trip->status === TripStatus::READY_FOR_DEPARTURE,
                // Kesiapan adalah pendukung keputusan (R-014): yang disebut hanya apakah
                // pemeriksaan sudah dilakukan dan apa hasilnya, bukan "aman".
                'kesiapan' => $trip->latestReadinessCheck?->computed_state->label(),
                'urls' => [
                    'start' => route('trips.start', $trip),
                    'show' => route('trips.show', $trip),
                    'readiness' => route('trips.readiness', $trip),
                ],
            ])->values()->all(),
        ]);
    }
}
