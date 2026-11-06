<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\PaginasiInertia;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-19 penampil jejak audit. Baca-saja: jejak audit tidak pernah dihapus.
 */
class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $action = (string) $request->query('action', '');

        return Inertia::render('Admin/AuditLogs', [
            'title' => 'Audit Log',
            'action' => $action,
            'logs' => PaginasiInertia::props(
                AuditLog::query()
                    ->with('actor:id,name')
                    ->when($action, fn ($query, $a) => $query->where('action', 'like', "%{$a}%"))
                    ->orderByDesc('created_at')
                    ->paginate(20)
                    ->withQueryString(),
                fn (AuditLog $log) => [
                    'id' => $log->id,
                    'waktu' => Timezone::display($log->created_at, Timezone::DEFAULT),
                    'aktor' => $log->actor?->name ?? 'Sistem',
                    'aksi' => $log->action,
                    'entitas' => class_basename($log->entity_type ?? '-').($log->entity_id ? ' #'.$log->entity_id : ''),
                    'perubahan' => $log->after
                        ? json_encode(['before' => $log->before, 'after' => $log->after], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        : null,
                ],
            ),
        ]);
    }
}
