<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\ModerationAction as ModerationActionType;
use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ModerateReportRequest;
use App\Http\Resources\Moderation\ReportResource;
use App\Models\ModerationAction;
use App\Models\TrailConditionReport;
use App\Services\AuditLogService;
use App\Support\PaginasiInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-17 antrean moderasi laporan komunitas. Laporan yang belum lolos tidak pernah tampil
 * publik (scope visibleToPublic), dan laporan tidak pernah mengubah status resmi (R-010).
 */
class ModerationQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('moderate', TrailConditionReport::class);

        // Tanpa parameter: yang menunggu. `?filter=` kosong berarti semua.
        // Keberadaan kunci, bukan nilainya: `?filter=` sudah menjadi null di sini.
        $filter = $request->query->has('filter')
            ? (string) $request->query('filter')
            : ModerationStatus::PENDING->value;

        return Inertia::render('Moderation/Queue', [
            'title' => 'Moderasi Laporan',
            'filter' => $filter,
            'statuses' => array_map(fn (ModerationStatus $s) => ['value' => $s->value, 'label' => $s->label()], ModerationStatus::cases()),
            // Menolak atau menghapus membuang intel lapangan yang tidak dapat diambil
            // ulang: pendakian itu sudah lewat. Menyetujui dan menandai tidak dihalangi,
            // karena keduanya dapat diubah lagi.
            'actions' => array_map(fn (ModerationActionType $a) => [
                'value' => $a->value,
                'label' => $a->label(),
                'konfirmasi' => in_array($a, [ModerationActionType::REJECT, ModerationActionType::REMOVE], true)
                    ? $a->label().' laporan ini? Pendakian yang dilaporkan sudah lewat, jadi intelnya tidak dapat diambil ulang.'
                    : null,
            ], ModerationActionType::cases()),
            'reports' => PaginasiInertia::props(
                TrailConditionReport::query()
                    ->when($filter, fn ($query, $f) => $query->where('moderation_status', $f))
                    ->with('trail.mountain', 'user:id,name', 'segment:id,name')
                    ->orderByDesc('created_at')
                    ->paginate(10)
                    ->withQueryString(),
                fn (TrailConditionReport $r) => (new ReportResource($r))->resolve($request),
            ),
        ]);
    }

    public function act(ModerateReportRequest $request, TrailConditionReport $report, AuditLogService $audit): RedirectResponse
    {
        $actionType = $request->tindakan();
        $before = ['moderation_status' => $report->moderation_status->value];

        $report->update([
            'moderation_status' => $actionType->resultingStatus()->value,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        ModerationAction::create([
            'moderator_id' => $request->user()->id,
            'moderatable_type' => $report->getMorphClass(),
            'moderatable_id' => $report->id,
            'action' => $actionType->value,
            'reason' => $request->validated('reason'),
        ]);

        $audit->record($request->user(), 'report.moderated', $report, $before, [
            'moderation_status' => $report->moderation_status->value,
        ]);

        // Setiap tindakan admin lain memberi tahu bahwa ia berhasil. Tanpa ini moderator
        // menekan tombol lalu menebak apakah tindakannya tercatat, terutama pada filter
        // "Semua" tempat kartunya tidak hilang dari layar.
        return back()->with('status', match ($actionType) {
            ModerationActionType::APPROVE => 'Laporan disetujui dan kini tampil untuk pendaki lain.',
            ModerationActionType::REJECT => 'Laporan ditolak. Alasannya ditampilkan kepada pelapor di riwayatnya.',
            ModerationActionType::FLAG => 'Laporan ditandai untuk ditinjau ulang.',
            ModerationActionType::REMOVE => 'Laporan dihapus dari tampilan publik.',
        });
    }
}
