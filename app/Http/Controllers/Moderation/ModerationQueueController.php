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
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-17 antrean moderasi laporan komunitas. Laporan yang belum lolos tidak pernah tampil
 * publik (scope visibleToPublic), dan laporan tidak pernah mengubah status resmi (R-010).
 * Setiap tindakan boleh diambil dari status apa pun; setuju dan tolak hanya dijalankan bila
 * status dan isi laporan masih sama dengan yang dibaca moderator di kartunya (act()).
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

    /**
     * Moderator hanya memutus keadaan yang ia baca (ruling W6-6c dan W6-6q). Setuju dan tolak
     * membawa dua hal dari kartunya: status yang tampil di kartu (status_dibaca) dan sidik isi.
     * Di dalam transaksi dengan lockForUpdate, baris dibaca ulang lalu dibandingkan:
     * - status berbeda dari status_dibaca (moderator lain sudah bertindak lebih dulu): tindakan
     *   ditolak dengan kalimat status;
     * - status sama tetapi sidik berbeda (pelapor memakai "Ubah sebelum dimoderasi" di antara
     *   moderator membaca dan menekan tombol): tindakan ditolak dengan kalimat isi, bukan
     *   diam-diam menerbitkan isi yang tidak pernah dibaca moderator;
     * - selain itu tindakan dijalankan, dari status apa pun yang dibaca, termasuk FLAGGED dan
     *   APPROVED. Penjagaan "harus PENDING" dari W6-6c dibuang (ruling W6-6q, B-1): tujuannya
     *   moderator memutus keadaan yang ia baca, bukan mengubah alur moderasi (R-021), dan
     *   laporan FLAGGED sempat buntu karenanya.
     *
     * Tandai dan hapus tidak membawa sidik maupun status dan tidak melewati pemeriksaan ini:
     * keduanya tidak menerbitkan isi, dan hapus dipakai juga pada laporan yang sudah APPROVED.
     * Penolakan dikirim di errors.action, yang dirender antrean (Moderation/Queue.tsx) di kartu
     * laporan itu atau, bila kartunya tidak lagi ada di daftar, di puncak daftar.
     */
    public function act(ModerateReportRequest $request, TrailConditionReport $report, AuditLogService $audit): RedirectResponse
    {
        $actionType = $request->tindakan();
        $memutusIsi = in_array($actionType, [ModerationActionType::APPROVE, ModerationActionType::REJECT], true);
        $penolakan = null;

        DB::transaction(function () use ($report, $actionType, $memutusIsi, $request, $audit, &$penolakan) {
            $terkini = TrailConditionReport::query()->lockForUpdate()->findOrFail($report->id);

            if ($memutusIsi && $terkini->moderation_status->value !== $request->validated('status_dibaca')) {
                $penolakan = 'Status laporan ini sudah berubah sejak Anda membukanya, jadi tindakan Anda tidak dijalankan.';

                return null;
            }

            if ($memutusIsi && $terkini->sidik() !== $request->validated('sidik')) {
                $penolakan = 'Isi laporan ini berubah sejak Anda membukanya, jadi tindakan Anda tidak dijalankan. Baca isi terbarunya sebelum memutus.';

                return null;
            }

            $before = ['moderation_status' => $terkini->moderation_status->value];

            $terkini->update([
                'moderation_status' => $actionType->resultingStatus()->value,
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
            ]);

            ModerationAction::create([
                'moderator_id' => $request->user()->id,
                'moderatable_type' => $terkini->getMorphClass(),
                'moderatable_id' => $terkini->id,
                'action' => $actionType->value,
                'reason' => $request->validated('reason'),
            ]);

            $audit->record($request->user(), 'report.moderated', $terkini, $before, [
                'moderation_status' => $terkini->moderation_status->value,
            ]);

            return $terkini;
        });

        if ($penolakan !== null) {
            return back()->withErrors(['action' => $penolakan]);
        }

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
