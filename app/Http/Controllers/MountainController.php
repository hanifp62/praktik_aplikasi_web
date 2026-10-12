<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\MountainResource;
use App\Models\Mountain;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API baca publik gunung, dari PR #1 tim (feature/db-migration), dipertahankan di atas skema
 * kanonik. Hanya gunung yang tidak diarsipkan dan punya sedikitnya satu jalur terbit (R-022);
 * jalur yang ikut juga hanya yang terbit. Kolom internal tidak ikut (MountainResource).
 */
class MountainController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $gunung = Mountain::query()
            ->whereNull('archived_at')
            ->whereHas('trails', fn (Builder $q) => $q->published())
            ->withCount(['trails' => fn (Builder $q) => $q->published()])
            ->orderBy('name')
            ->paginate(50);

        return MountainResource::collection($gunung);
    }

    public function show(int $id): MountainResource
    {
        $gunung = Mountain::query()
            ->whereNull('archived_at')
            ->whereHas('trails', fn (Builder $q) => $q->published())
            ->with(['trails' => fn ($q) => $q->published()->orderBy('name')])
            ->findOrFail($id);

        return new MountainResource($gunung);
    }
}
