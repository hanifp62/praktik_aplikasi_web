<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\Trails\PenilaianKecocokan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Penilaian kecocokan satu jalur (spec v3 bagian 6.5 dan 9.3), opsional untuk satu trip
 * (`?trip=`). Hanya jalur terbit yang dinilai (R-022); jalur draf dan arsip menjawab 404.
 *
 * Trip dibaca dari URL, jadi kepemilikannya diperiksa Policy (PRD §77): trip orang lain
 * ditolak 403 seperti halaman trip itu sendiri, dan trip untuk jalur lain menjawab 404
 * karena penilaiannya bukan tentang jalur di URL.
 */
class TrailFitAssessmentController extends Controller
{
    public function __invoke(Request $request, Trail $trail, PenilaianKecocokan $halaman): Response
    {
        abort_unless($trail->terbit(), 404);

        $trail->load('mountain');
        $trip = null;

        if ($request->query->has('trip')) {
            $trip = TripPlan::query()->findOrFail($request->integer('trip'));
            $this->authorize('view', $trip);
            abort_unless($trip->trail_id === $trail->id, 404);
        }

        return Inertia::render('Trails/Fit', [
            'title' => 'Penilaian kecocokan '.$trail->name,
        ] + $halaman->props($trail, $request->user(), $trip));
    }
}
