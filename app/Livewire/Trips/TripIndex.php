<?php

namespace App\Livewire\Trips;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Trip Saya')]
class TripIndex extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.trips.trip-index', [
            'trips' => auth()->user()->tripPlans()
                ->with('trail.mountain', 'latestReadinessCheck')
                ->orderByDesc('planned_date')
                ->paginate(10),
        ]);
    }
}
