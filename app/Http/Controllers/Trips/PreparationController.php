<?php

namespace App\Http\Controllers\Trips;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdatePreparationItemRequest;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use App\Services\Trips\TripSteps;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-09 daftar persiapan tiga keadaan. "Belum dikonfirmasi" tidak pernah berarti
 * "tidak dimiliki" (PRD §37, BR-10). Pengganti komponen PreparationChecklist.
 */
class PreparationController extends Controller
{
    public function show(TripPlan $trip, PreparationService $preparation): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'preparationItems');

        if ($trip->preparationItems->isEmpty()) {
            $preparation->generateFor($trip);
            $trip->load('preparationItems');
        }

        $perKategori = $trip->preparationItems->groupBy(fn (TripPreparationItem $item) => $item->category->value);
        $hitungan = TripSteps::hitungPersiapan($trip->preparationItems);

        return Inertia::render('Trips/Preparation', [
            'title' => 'Persiapan Pendakian',
            'trip' => [
                'id' => $trip->id,
                'keterangan' => $trip->name.' - '.$trip->trail->name.', '.$trip->trail->mountain->name,
            ],
            // Urutan kategori tetap, ditetapkan di kode; kategori kosong tidak tampil.
            'kategori' => collect(PreparationCategory::cases())
                ->filter(fn (PreparationCategory $k) => $perKategori->has($k->value))
                ->map(fn (PreparationCategory $k) => [
                    'nama' => $k->label(),
                    'items' => $perKategori[$k->value]->map(fn (TripPreparationItem $item) => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'description' => $item->description,
                        'kritis' => (bool) $item->is_critical,
                        'status' => $item->status->value,
                    ])->values()->all(),
                ])
                ->values()
                ->all(),
            'statuses' => array_map(fn (PreparationStatus $s) => ['value' => $s->value, 'label' => $s->label()], PreparationStatus::cases()),
            'kemajuan' => [
                'dikonfirmasi' => $hitungan['dikonfirmasi'],
                'berlaku' => $hitungan['berlaku'],
                'teks' => TripSteps::teksPersiapan($hitungan),
            ],
        ]);
    }

    public function update(UpdatePreparationItemRequest $request, TripPlan $trip, int $item, PreparationService $preparation): RedirectResponse
    {
        // Dicari di dalam trip ini saja: butir milik trip lain menjawab 404, bukan
        // berubah karena id-nya ditebak (IDOR).
        $butir = TripPreparationItem::where('trip_plan_id', $trip->id)->findOrFail($item);

        $preparation->updateStatus($butir, PreparationStatus::from($request->validated('status')));

        return redirect()->route('trips.preparation', $trip);
    }

    public function regenerate(TripPlan $trip, PreparationService $preparation): RedirectResponse
    {
        $this->authorize('update', $trip);

        $preparation->generateFor($trip);

        return redirect()->route('trips.preparation', $trip)
            ->with('status', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.');
    }
}
