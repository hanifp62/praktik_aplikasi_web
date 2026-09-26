<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrailHeaderResource;
use App\Models\Trail;
use App\Services\Trails\TrailDetailPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rincian satu jalur (FR-07). Jalur terarsip ditarik dari peredaran dan menjawab 404;
 * jalur yang belum terbit tetap dapat dibuka lewat tautan langsung, tetapi yang tampil
 * keadaan menunggu, bukan rincian setengah jadi yang terbaca seperti rincian utuh.
 */
class TrailDetailController extends Controller
{
    public function __invoke(Request $request, Trail $trail, TrailDetailPage $halaman): Response
    {
        abort_if($trail->archived_at !== null, 404);

        $trail->load('mountain', 'segments', 'checkpoints', 'dataSource');
        $kepala = [
            'title' => $trail->name.' - '.$trail->mountain->name,
            'trail' => (new TrailHeaderResource($trail))->resolve($request),
        ];

        if (! $trail->is_published) {
            return Inertia::render('Trails/Awaiting', $kepala + $halaman->menunggu($trail));
        }

        return Inertia::render('Trails/Show', $kepala + [
            'pengumuman' => session('pengumuman', ''),
        ] + $halaman->props($trail, $request->user()));
    }
}
