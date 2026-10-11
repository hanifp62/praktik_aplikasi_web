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
 *
 * Sesudahnya pendaki kembali ke halaman tempat sakelarnya ditekan (ruling W4-FC): sakelar di
 * Persiapan tidak boleh melempar pendaki dari daftar yang sedang ia kerjakan. Halaman asal
 * hanya dipilih dari dua rute trip itu sendiri lewat isian `kembali` yang divalidasi, tanpa
 * Referer, jadi tidak ada redirect terbuka.
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

        $dariPersiapan = $request->validated('kembali') === 'persiapan';

        // Di Persiapan kabarnya dibacakan di wilayah aksi dekat kartu pengingat (kabar-aksi);
        // ruang kerja membacakannya di wilayah kabar dekat kepala halamannya (status). W4-FK N1.
        return redirect()->route($dariPersiapan ? 'trips.preparation' : 'trips.show', $trip)->with($dariPersiapan ? 'kabar-aksi' : 'status', $aktif
            ? 'Pengingat dinyalakan. Surel dikirim ke alamat akun Anda sesuai jadwal pengingat.'
            : 'Pengingat dimatikan. Pengingat yang belum terkirim dibatalkan.');
    }
}
