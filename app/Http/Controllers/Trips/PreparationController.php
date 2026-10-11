<?php

namespace App\Http\Controllers\Trips;

use App\Enums\PreparationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdatePreparationItemRequest;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use App\Services\Trips\HalamanPersiapan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-09 daftar persiapan tiga keadaan. "Belum dikonfirmasi" tidak pernah berarti
 * "tidak dimiliki" (PRD §37, BR-10). Pengganti komponen PreparationChecklist.
 */
class PreparationController extends Controller
{
    public function show(TripPlan $trip, PreparationService $preparation, HalamanPersiapan $halaman): Response
    {
        $this->authorize('view', $trip);

        // Urutan templat ikut dimuat (satu kueri), supaya butir tampil menurut sort_order-nya.
        $trip->load('trail.mountain', 'preparationItems.preparationItem:id,sort_order', 'reminders');

        // Daftar kosong dibangkitkan saat dibuka, kecuali untuk trip yang selesai atau batal:
        // daftar mereka catatan, dan membuka catatan tidak menulis apa pun (W4-4d).
        if ($trip->preparationItems->isEmpty() && PreparationService::penolakanDaftar($trip) === null) {
            $preparation->generateFor($trip);
            $trip->load('preparationItems.preparationItem:id,sort_order');
        }

        return Inertia::render('Trips/Preparation', [
            'title' => 'Persiapan Pendakian',
            ...$halaman->props($trip),
        ]);
    }

    /**
     * Satu status butir, juga untuk Urungkan: halaman mengirim status yang diinginkan lewat
     * rute yang sama, dan server membalas BUTIR MANA dan status yang BENAR-BENAR tersimpan
     * sebelum permintaan ini lewat satu payload flash berkunci (tinjauan Tugas 4 I1b dan
     * putaran 2), bukan pandangan klien yang bisa basi bila butir ini sudah diubah di tab
     * atau perangkat lain. Id-nya disebut supaya klien dapat menolak payload yang bukan
     * milik permintaan yang baru dikirimnya; ini satu-satunya penjaga, bukan pertahanan
     * berlapis, karena serialisasi di Preparation.tsx berlaku per tab sedangkan flash ini
     * per sesi, jadi dua tab dengan cookie yang sama tetap bisa bertukar payload (tinjauan
     * putaran 3 #3). Toast dan Urungkan di klien memakai flash ini, dan tidak ditawarkan
     * bila statusnya tidak berubah (pola W3-4b). Hanya status yang diambil dari badan
     * permintaan; trip, kritis, label, dan kategori tidak pernah berubah di sini.
     */
    public function update(UpdatePreparationItemRequest $request, TripPlan $trip, int $item, PreparationService $preparation): RedirectResponse
    {
        // Dicari di dalam trip ini saja: butir milik trip lain menjawab 404, bukan
        // berubah karena id-nya ditebak (IDOR).
        $butir = TripPreparationItem::where('trip_plan_id', $trip->id)->findOrFail($item);
        $status = PreparationStatus::from($request->validated('status'));

        if ($penolakan = PreparationService::penolakanKonfirmasi($trip, $butir, $status)) {
            throw ValidationException::withMessages(['status' => $penolakan]);
        }

        $sebelum = $butir->status;
        $preparation->updateStatus($butir, $status);

        return redirect()->route('trips.preparation', $trip)
            ->with('butir-diubah', [
                'id' => $butir->id,
                'sebelum' => $sebelum->value,
                'berubah' => $sebelum !== $status,
            ]);
    }

    public function regenerate(TripPlan $trip, PreparationService $preparation): RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($penolakan = PreparationService::penolakanDaftar($trip)) {
            throw ValidationException::withMessages(['daftar' => $penolakan]);
        }

        $preparation->generateFor($trip);

        // Kabar aksi di halaman yang sama, dibacakan di wilayah dekat tombolnya; `status` dipakai
        // kabar yang datang bersama perpindahan halaman (W4-FK N1).
        return redirect()->route('trips.preparation', $trip)
            ->with('kabar-aksi', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.');
    }
}
