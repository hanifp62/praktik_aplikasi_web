<?php

namespace App\Livewire\Trips;

use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Trip Saya')]
class TripIndex extends Component
{
    use WithPagination;

    public function render(OfficialStatusService $officialStatus, TrailFitService $trailFit)
    {
        $trips = auth()->user()->tripPlans()
            ->with('trail.mountain', 'latestReadinessCheck')
            ->orderByDesc('planned_date')
            ->paginate(10);

        // Bukan collect(): OfficialStatusService dan PermitService (lewat forTrails())
        // men-type-hint Eloquent\Collection secara ketat dan melempar TypeError pada
        // Support\Collection biasa. Ini sudah menggigit sekali di Tugas 3.
        $jalurDitampilkan = EloquentCollection::make(
            $trips->pluck('trail')->filter()->unique('id')->values()->all()
        );

        // Kecocokan dasar, tanpa goal: daftar trip adalah permukaan pemindaian, dan
        // kecocokan tajam yang sadar-rencana sudah tinggal di halaman trip itu sendiri.
        // Menyalakan kecocokan per-goal di sini berarti satu goal berbeda per baris, dan
        // itu batching yang berbeda sama sekali tanpa imbalan sepadan.
        //
        // Sama seperti TrailIndex: pengguna tanpa profil lengkap tidak dinilai sama
        // sekali, karena menilai tanpa profil menghasilkan label yang terlihat pasti dan
        // berdasar ketiadaan (§91).
        $ringkasanFit = auth()->user()->hasCompletedProfile()
            ? $trailFit->forTrails(auth()->user(), $jalurDitampilkan)
            : [];

        return view('livewire.trips.trip-index', [
            'trips' => $trips,
            // Dimuat sekali untuk seluruh halaman. Membiarkan tiap baris mencari status
            // jalurnya sendiri menambah satu query per trip pada daftar berpaginasi.
            'statusJalur' => $officialStatus->effectiveStatusesForTrails($jalurDitampilkan),
            'ringkasanFit' => $ringkasanFit,
        ]);
    }
}
