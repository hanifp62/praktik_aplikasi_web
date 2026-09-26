<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdateTripReminderRequest;
use App\Models\TripPlan;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Http\RedirectResponse;

/**
 * Menyalakan atau mematikan pengingat perjalanan satu trip (FR-24). Otorisasi lewat
 * UpdateTripReminderRequest (Policy pemilik trip), jadwalnya lewat
 * TripReminderScheduler.
 */
class TripReminderController extends Controller
{
    public function update(UpdateTripReminderRequest $request, TripPlan $trip, TripReminderScheduler $pengingat): RedirectResponse
    {
        if (! $pengingat->dapatDiatur($trip)) {
            return redirect()->route('trips.show', $trip)
                ->with('penolakan-trip', 'Pengingat hanya dapat diatur untuk trip yang belum dimulai.');
        }

        $aktif = $request->boolean('aktif');

        $trip->update(['reminders_enabled' => $aktif]);
        $pengingat->sinkronkan($trip);

        return redirect()->route('trips.show', $trip)->with('status', $aktif
            ? 'Pengingat dinyalakan. Surel dikirim ke alamat akun Anda sesuai jadwal di bawah.'
            : 'Pengingat dimatikan. Pengingat yang belum terkirim dibatalkan.');
    }
}
