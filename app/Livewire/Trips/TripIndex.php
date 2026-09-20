<?php

namespace App\Livewire\Trips;

use App\Services\OfficialStatusService;
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

    public function render(OfficialStatusService $officialStatus)
    {
        $trips = auth()->user()->tripPlans()
            ->with('trail.mountain', 'latestReadinessCheck')
            ->orderByDesc('planned_date')
            ->paginate(10);

        return view('livewire.trips.trip-index', [
            'trips' => $trips,
            // Dimuat sekali untuk seluruh halaman. Membiarkan tiap baris mencari status
            // jalurnya sendiri menambah satu query per trip pada daftar berpaginasi.
            'statusJalur' => $officialStatus->effectiveStatusesForTrails(
                EloquentCollection::make($trips->pluck('trail')->filter()->unique('id')->values()->all())
            ),
        ]);
    }
}
