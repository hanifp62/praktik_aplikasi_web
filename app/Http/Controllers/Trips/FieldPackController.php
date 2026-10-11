<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\Trips\PaketLapangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Manifest dan bagian paket lapangan luring (spec v3 bagian 10.2 dan 10.6; rencana induk W6
 * butir 4; ruling W6-R3). Pemilik trip saja (Policy yang sama dengan trip), hanya jalur terbit,
 * dan `Cache-Control: private, no-store`: rencana pribadi tidak boleh tersimpan di cache mana
 * pun selain toko paket pendaki sendiri. Rutenya menjawab 404 selama sakelar
 * hiking.field_pack.enabled mati (middleware fitur, Q10 dan Q14).
 */
class FieldPackController extends Controller
{
    private const TANPA_SIMPAN = ['Cache-Control' => 'private, no-store'];

    public function manifest(Request $request, TripPlan $trip, PaketLapangan $paket): JsonResponse
    {
        $this->authorize('view', $trip);
        abort_unless($paket->bolehDipaket($trip), 404);

        return response()->json($paket->manifest($trip, $request->user()), 200, self::TANPA_SIMPAN);
    }

    public function section(Request $request, TripPlan $trip, string $bagian, PaketLapangan $paket): Response
    {
        $this->authorize('view', $trip);
        abort_unless($paket->bolehDipaket($trip), 404);

        return response($paket->teksBagian($trip, $request->user(), $bagian), 200, ['Content-Type' => 'application/json', ...self::TANPA_SIMPAN]);
    }
}
