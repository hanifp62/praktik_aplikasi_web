<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\HikingHistory;
use App\Services\Community\HasilPendakian;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat pendakian (FR-14): hanya milik pengguna yang masuk, dimuat sekaligus supaya
 * jumlah query tidak tumbuh bersama jumlah riwayat.
 */
class HikingHistoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $entri = $request->user()->hikingHistory()
            ->with('trail.mountain', 'tripPlan.hikingSession', 'conditionReport.moderationActions')
            ->orderByDesc('completed_at')
            ->paginate(10);

        return Inertia::render('Community/HikingHistory', [
            'title' => 'Riwayat Pendakian',
            'entri' => [
                'data' => $entri->getCollection()->map(fn (HikingHistory $h) => [
                    'id' => $h->id,
                    'tripId' => $h->trip_plan_id,
                    'trailId' => $h->trail_id,
                    'jalur' => $h->trail?->name,
                    'gunung' => $h->trail?->mountain?->name,
                    'tanggal' => $h->completed_at->translatedFormat('d M Y'),
                    'jenisTrip' => $h->trip_type?->label(),
                    'hasil' => $h->completion_state?->label(),
                    'persiapan' => $h->preparation_completion_percent.'%',
                    // Jarak milik jalurnya, bukan rekaman langkah pendaki; yang belum
                    // dicatat disebut belum diketahui, bukan nol (§95).
                    'jarak' => $h->trail?->distance_km !== null
                        ? number_format((float) $h->trail->distance_km, 1, ',', '.').' km'
                        : 'belum diketahui',
                    'durasi' => HasilPendakian::durasiSesi($h->tripPlan?->hikingSession) ?? 'tidak tercatat',
                    'catatan' => $h->personal_notes,
                    'laporan' => $h->conditionReport === null ? null : [
                        'status' => $h->conditionReport->moderation_status->label(),
                        // Pelapor berhak tahu apa yang salah (PRD §57); alasan pada
                        // persetujuan adalah catatan internal dan tidak ikut.
                        'alasanDitolak' => $h->conditionReport->rejectionReason(),
                    ],
                ])->values()->all(),
                'links' => $entri->linkCollection()->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])->all(),
            ],
            'peringatanFoto' => session('foto-tidak-disertakan'),
        ]);
    }
}
