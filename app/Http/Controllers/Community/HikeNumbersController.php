<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\UpdateHikeNumbersRequest;
use App\Models\TripPlan;
use Illuminate\Http\RedirectResponse;

/**
 * "Ubah angka" ringkasan pendakian (spec v3 bagian 6.13): angka pendaki menggantikan angka yang
 * diturunkan dari rekaman atau data jalur, dan ringkasan menyebut bahwa angkanya diubah.
 * Pemilik dan trip dari rute, bukan dari isian.
 */
class HikeNumbersController extends Controller
{
    public function __invoke(UpdateHikeNumbersRequest $request, TripPlan $trip): RedirectResponse
    {
        $riwayat = $trip->history;

        abort_if($riwayat === null, 404);

        $jarak = $request->validated('jarak_km');

        $riwayat->update([
            'recorded_distance_m' => $jarak === null ? null : (int) round((float) $jarak * 1000),
            'recorded_gain_m' => $request->validated('naik_m'),
            'recorded_moving_minutes' => $request->validated('waktu_menit'),
            'recorded_highest_m' => $request->validated('tertinggi_m'),
            'numbers_edited_at' => now(),
        ]);

        return redirect()->route('history.summary', $trip)->with('status', 'Angka pendakian diperbarui.');
    }
}
