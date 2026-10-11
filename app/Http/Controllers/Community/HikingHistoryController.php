<?php

namespace App\Http\Controllers\Community;

use App\Enums\PreparationStatus;
use App\Http\Controllers\Controller;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Services\Community\HasilPendakian;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat pendakian (FR-14): hanya milik pengguna yang masuk, dimuat sekaligus supaya
 * jumlah query tidak tumbuh bersama jumlah riwayat.
 *
 * Persiapan ditulis sebagai hitungan butir, bukan persen (ruling AF-4, audit E2E I-4; R-005,
 * R-007). Kolom preparation_completion_percent bernilai 0 untuk trip tanpa butir yang berlaku,
 * jadi "0%" menulis data yang tidak ada sebagai nol. Hitungannya dari butir persiapan trip itu,
 * dengan "berlaku" yang sama dengan TripPlan::preparationCompletionPercent() (status selain
 * NOT_APPLICABLE), lewat tiga withCount pada eager load tripPlan: satu kueri untuk semua entri
 * halaman. Butir trip yang sudah selesai atau batal tidak dapat diubah lagi
 * (PreparationService::penolakanDaftar), jadi hitungannya tetap keadaan saat riwayat dicatat.
 */
class HikingHistoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $entri = $request->user()->hikingHistory()
            ->with([
                'trail.mountain',
                'tripPlan' => fn (Builder $kueri) => $kueri->withCount([
                    'preparationItems as butir_jumlah',
                    'preparationItems as butir_berlaku' => fn (Builder $butir) => $butir->where('status', '!=', PreparationStatus::NOT_APPLICABLE->value),
                    'preparationItems as butir_siap' => fn (Builder $butir) => $butir->where('status', PreparationStatus::CONFIRMED->value),
                ]),
                'tripPlan.hikingSession',
                'conditionReport.moderationActions',
            ])
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
                    'persiapan' => self::persiapan($h->tripPlan),
                    // Jarak milik jalurnya, bukan rekaman langkah pendaki; yang belum
                    // dicatat disebut belum diketahui, bukan nol (§95).
                    'jarak' => $h->trail?->distance_km !== null
                        ? number_format((float) $h->trail->distance_km, 1, ',', '.').' km'
                        : 'belum diketahui',
                    'durasi' => HasilPendakian::durasiSesi($h->tripPlan?->hikingSession) ?? 'tidak tercatat',
                    'catatan' => $h->personal_notes,
                    'laporan' => $h->conditionReport === null ? null : [
                        'status' => $h->conditionReport->moderation_status->labelUntukPelapor(),
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

    /**
     * Kalimat persiapan satu entri dari hitungan withCount di atas, satu cabang per keadaan:
     * daftar tidak dibuat, semua butir ditandai Tidak berlaku (bukan "tidak dibuat", pola
     * TripSteps::teksPersiapan), atau x dari y butir yang berlaku sudah siap.
     */
    private static function persiapan(?TripPlan $trip): string
    {
        $jumlah = (int) ($trip?->butir_jumlah ?? 0);
        $berlaku = (int) ($trip?->butir_berlaku ?? 0);

        return match (true) {
            $jumlah === 0 => 'Daftar persiapan tidak dibuat.',
            $berlaku === 0 => sprintf('Persiapan: semua %d butir ditandai Tidak berlaku', $jumlah),
            default => sprintf('Persiapan: %d dari %d butir siap', (int) $trip->butir_siap, $berlaku),
        };
    }
}
