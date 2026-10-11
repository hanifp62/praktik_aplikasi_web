<?php

namespace App\Http\Controllers\Trips;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdateTripReturnRequest;
use App\Models\TripPlan;
use App\Services\Trips\CetakTrip;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alat keselamatan trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4): versi
 * cetak dan jam kembali untuk pesan rencana. Pemilik trip saja (Policy yang sama dengan trip);
 * rutenya menjawab 404 selama sakelar hiking.field_safety.enabled mati (middleware fitur, Q14).
 */
class TripSafetyController extends Controller
{
    public function print(Request $request, TripPlan $trip, CetakTrip $cetak): Response
    {
        $this->authorize('view', $trip);

        return Inertia::render('Trips/Print', [
            'title' => 'Cetak '.$trip->name,
            ...$cetak->untuk($trip, $request->user()),
        ]);
    }

    /**
     * Jam kembali hanya untuk trip yang belum selesai atau batal, lewat UPDATE bersyarat pada
     * status (pra-tinjau PT7-8, pola W6-5g): trip yang diselesaikan di tab lain sesudah halaman
     * ini dimuat tidak menerima jam kembali baru, dan pendaki tidak diberi kabar "sudah
     * disimpan" untuk kartu yang sudah lenyap. Gerbangnya sama dengan prop keselamatan di ruang
     * kerja (TripStatus::isActive()). UPDATE yang sama juga bersyarat pada tanggal dan jam mulai
     * yang dimuat (ruling W6-7g), dengan penolakan sendiri untuk mulai yang berpindah.
     */
    public function updateReturn(UpdateTripReturnRequest $request, TripPlan $trip): RedirectResponse
    {
        $zona = Timezone::forTrail($trip->trail);

        // Compare-and-swap pada mulai trip (ruling W6-7g, I-1 usulan B): validasi membandingkan
        // jam kembali dengan mulai yang dimuat pengikatan rute, jadi UPDATE hanya mengenai baris
        // yang mulainya masih sama. Ubah rencana dari tab lain yang memindah mulai di antaranya
        // menghasilkan penolakan, bukan jam kembali yang dihitung dari mulai lama. Nilai mentah
        // start_time dibandingkan apa adanya, sehingga bentuk kolom time PostgreSQL tidak berpengaruh.
        $terupdate = TripPlan::query()
            ->whereKey($trip->id)
            ->whereNotIn('status', [TripStatus::COMPLETED->value, TripStatus::CANCELLED->value])
            ->whereDate('planned_date', $trip->planned_date->toDateString())
            ->where(fn ($q) => $trip->start_time === null ? $q->whereNull('start_time') : $q->where('start_time', $trip->getRawOriginal('start_time')))
            ->update([
                'expected_return_at' => Carbon::createFromFormat('Y-m-d H:i', $request->validated('kembali_tanggal').' '.$request->validated('kembali_jam'), $zona)->utc(),
                'registration_number' => $request->validated('registrasi'),
            ]);

        if ($terupdate === 0) {
            $sekarang = $trip->fresh();

            return back()->with('penolakan-trip', $sekarang !== null && $sekarang->status->isActive()
                ? 'Jam kembali tidak diubah: tanggal atau jam mulai trip berubah sejak halaman ini dibuka. Periksa rencananya, lalu isi jam kembali lagi.'
                : 'Jam kembali tidak diubah: trip ini sudah selesai atau dibatalkan.');
        }

        return back()->with('status', 'Jam kembali disimpan. Pesan rencana sudah memakai jam ini.');
    }
}
