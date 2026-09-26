<?php

namespace App\Http\Controllers\Trips;

use App\Enums\ReadinessState;
use App\Http\Controllers\Controller;
use App\Http\Resources\TripReadinessResource;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Services\ReadinessService;
use App\Services\Trips\PreDepartureCheck;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-10 kesiapan: route fit + persiapan + kondisi terkini (PRD §38-39). Dukungan
 * keputusan, bukan izin keselamatan (R-014). Pengganti komponen ReadinessDashboard.
 *
 * Membuka halaman hanya menghitung, tidak menyimpan. Baris ReadinessCheck baru ditulis
 * ketika pengguna meminta perhitungan ulang atau mengonfirmasi pre-departure check.
 */
class ReadinessController extends Controller
{
    public function show(Request $request, TripPlan $trip, ReadinessService $readiness): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain');
        $zona = Timezone::forTrail($trip->trail);

        $assessment = $readiness->compute($trip);
        $terakhir = $readiness->latestCheck($trip);
        $dikonfirmasi = (bool) $terakhir?->pre_departure_confirmed;

        return Inertia::render('Trips/Readiness', [
            'title' => 'Kesiapan Pendakian',
            'trip' => [
                'id' => $trip->id,
                'keterangan' => $trip->name.' - '.$trip->trail->name,
            ],
            // Tidak disimpan, hanya untuk ditampilkan.
            'check' => (new TripReadinessResource(new ReadinessCheck($assessment->toAttributes()), $zona))->resolve($request),
            'bolehKonfirmasi' => $assessment->state !== ReadinessState::NOT_RECOMMENDED,
            'preDeparture' => [
                'dikonfirmasi' => $dikonfirmasi,
                'dicatat' => $dikonfirmasi
                    ? (Timezone::display($terakhir->updated_at, $zona) ?? 'sebelumnya')
                    : null,
            ],
            'penolakan' => session('penolakan-kesiapan'),
        ]);
    }

    public function recompute(TripPlan $trip, ReadinessService $readiness): RedirectResponse
    {
        $this->authorize('update', $trip);

        $readiness->record($trip);

        return redirect()->route('trips.readiness', $trip)->with('status', 'Kesiapan dihitung ulang.');
    }

    public function confirm(Request $request, TripPlan $trip, PreDepartureCheck $check): RedirectResponse
    {
        $this->authorize('update', $trip);

        $trip->load('trail.mountain');
        $penolakan = $check->confirm($trip, $request->user());

        // Penolakan tidak memakai flash 'status' (alert hijau): kotak berhasil yang
        // berisi kabar gagal adalah pesan yang membantah dirinya sendiri.
        return $penolakan !== null
            ? redirect()->route('trips.readiness', $trip)->with('penolakan-kesiapan', $penolakan)
            : redirect()->route('trips.readiness', $trip)->with('status', 'Pre-departure check tercatat.');
    }
}
