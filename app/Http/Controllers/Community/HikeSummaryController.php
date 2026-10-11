<?php

namespace App\Http\Controllers\Community;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\Community\HasilPendakian;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ringkasan satu pendakian (spec v3 bagian 6.13). Pemilik URL bukan pemilik trip (PRD §77):
 * policy yang sama dengan trip.
 */
class HikeSummaryController extends Controller
{
    public function __invoke(Request $request, TripPlan $trip, HasilPendakian $hasil): Response
    {
        $this->authorize('view', $trip);

        // Trip yang belum selesai dan tidak dibatalkan belum punya hasil untuk diringkas, dan
        // ringkasan kosong akan terbaca seolah pendakiannya sudah usai. Trip yang dibatalkan
        // mendapat varian pendek dengan alasannya (DK-50).
        abort_unless($trip->history()->exists() || $trip->status === TripStatus::CANCELLED, 404);

        $props = $hasil->untuk($trip, $request->user());

        return Inertia::render('Community/HikeSummary', [
            // Ruling W6-6f (M-1): varian batal tidak punya "hasil pendakian" untuk diringkas.
            'title' => $props['varian'] === 'batal' ? 'Trip '.$props['jalur']['nama'].' dibatalkan' : 'Hasil pendakian '.$props['jalur']['nama'],
            ...$props,
            // Laporan dari ringkasan kembali ke sini; foto yang gagal dibersihkan disebut di sini.
            'peringatanFoto' => session('foto-tidak-disertakan'),
        ]);
    }
}
