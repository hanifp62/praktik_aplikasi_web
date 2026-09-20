<?php

namespace App\Livewire\History;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-14 hiking history.
 */
#[Layout('layouts.app')]
#[Title('Riwayat Pendakian')]
class HikingHistoryPage extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.history.hiking-history-page', [
            'entries' => auth()->user()->hikingHistory()
                ->with('trail.mountain', 'tripPlan', 'conditionReport.moderationActions')
                ->orderByDesc('completed_at')
                ->paginate(10),
        ]);
    }
}
