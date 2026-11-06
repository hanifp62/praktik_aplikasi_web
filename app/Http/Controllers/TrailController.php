<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\TrailResource;
use App\Models\Trail;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API baca publik jalur, dari PR #1 tim (feature/db-migration), dipertahankan di atas skema
 * kanonik. Hanya jalur terbit (R-022): jalur draf atau diarsipkan menjawab 404 seperti jalur yang
 * tidak ada, supaya keberadaannya tidak bocor.
 */
class TrailController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TrailResource::collection(
            Trail::query()->published()->with('mountain')->orderBy('name')->paginate(50)
        );
    }

    public function show(int $id): TrailResource
    {
        $jalur = Trail::query()
            ->published()
            ->with(['mountain', 'checkpoints' => fn ($q) => $q->orderBy('sequence')])
            ->findOrFail($id);

        return new TrailResource($jalur);
    }
}
