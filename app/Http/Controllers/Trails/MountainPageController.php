<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Resources\MountainHeaderResource;
use App\Models\Mountain;
use App\Services\Mountains\HalamanGunung;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman gunung terautentikasi (DK-44, spec v3 bagian 6.3): pintu dari Jelajah dan dari
 * rincian jalur ke seluruh jalur sebuah gunung. Gunung terarsip ditarik dari peredaran dan
 * menjawab 404, sama seperti jalur terarsip. Halaman publik SEO `/gunung/{slug}` tetap
 * mengikuti rencana D-B dan bukan halaman ini.
 */
class MountainPageController extends Controller
{
    public function __invoke(Request $request, Mountain $mountain, HalamanGunung $halaman): Response
    {
        abort_if($mountain->isArchived(), 404);

        return Inertia::render('Mountains/Show', [
            'title' => $mountain->name,
            'gunung' => (new MountainHeaderResource($mountain))->resolve($request),
            'pengumuman' => session('pengumuman', ''),
        ] + $halaman->props($mountain, $request->user()));
    }
}
