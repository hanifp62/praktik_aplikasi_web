<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-19 audit log viewer.
 */
#[Layout('layouts.app')]
#[Title('Audit Log')]
class AuditLogViewer extends Component
{
    use WithPagination;

    #[Url]
    public string $action = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function render()
    {
        return view('livewire.admin.audit-log-viewer', [
            'logs' => AuditLog::query()
                ->with('actor:id,name')
                ->when($this->action, fn ($query, $action) => $query->where('action', 'like', "%{$action}%"))
                ->orderByDesc('created_at')
                ->paginate(20),
        ]);
    }
}
