<?php

namespace App\Livewire\Admin;

use App\Models\ScheduledTaskRun;
use App\Services\SchedulerHealthService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Kesehatan tugas terjadwal (PRD §98).
 *
 * Halaman ini ada untuk satu bentuk kegagalan yang tidak pernah menghasilkan galat:
 * scheduler yang tidak berjalan. Data lama tetap di tempatnya, setiap halaman tetap
 * tampak normal, dan satu-satunya tanda adalah sesuatu yang tidak terjadi.
 */
#[Layout('layouts.app')]
#[Title('Kesehatan Sistem')]
class SystemHealth extends Component
{
    public function render(SchedulerHealthService $kesehatan)
    {
        return view('livewire.admin.system-health', [
            'tugas' => $kesehatan->report(),
            'perluDiurus' => $kesehatan->needsAttention(),
            'kesehatan' => $kesehatan,
            'riwayat' => ScheduledTaskRun::query()->latest('ran_at')->limit(20)->get(),
        ]);
    }
}
