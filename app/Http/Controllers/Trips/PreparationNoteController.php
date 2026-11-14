<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdatePreparationNoteRequest;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\Trips\CatatanButir;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Menulis atau menghapus catatan satu butir persiapan (ruling W7-R9). Hanya kolom catatan yang
 * berubah: status, label, kritis, dan kategori butir tidak pernah diambil dari permintaan.
 */
class PreparationNoteController extends Controller
{
    public function __invoke(UpdatePreparationNoteRequest $request, TripPlan $trip, int $item): RedirectResponse
    {
        // Dicari di dalam trip ini saja: butir milik trip lain menjawab 404 (IDOR).
        $butir = TripPreparationItem::where('trip_plan_id', $trip->id)->findOrFail($item);

        if ($penolakan = CatatanButir::penolakan($trip, $butir)) {
            throw ValidationException::withMessages(['catatan' => $penolakan]);
        }

        $butir->update(['note' => $request->catatan()]);

        return redirect()->route('trips.preparation', $trip);
    }
}
