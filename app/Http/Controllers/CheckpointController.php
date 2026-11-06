<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\CheckpointResource;
use App\Models\Checkpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API baca publik pos jalur, dari PR #1 tim (feature/db-migration), dipertahankan di atas skema
 * kanonik. Hanya pos milik jalur terbit (R-022), berurutan per jalur. `?trail_id=` menyaring satu
 * jalur; nilai yang bukan bilangan diabaikan.
 */
class CheckpointController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $pos = Checkpoint::query()
            ->whereHas('trail', fn (Builder $q) => $q->published())
            ->when($request->integer('trail_id'), fn (Builder $q, int $jalur) => $q->where('trail_id', $jalur))
            ->orderBy('trail_id')
            ->orderBy('sequence')
            ->paginate(100);

        return CheckpointResource::collection($pos);
    }
}
