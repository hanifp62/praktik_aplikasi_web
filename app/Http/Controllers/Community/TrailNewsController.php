<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Services\Community\KabarJalur;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kabar dari gunung yang diikuti pendaki: keadaan gunung, bukan aktivitas orang. Tidak
 * ada siapa mendaki apa, tidak ada peringkat, tidak ada yang mengundang perbandingan.
 */
class TrailNewsController extends Controller
{
    public function __invoke(Request $request, KabarJalur $kabar): Response
    {
        return Inertia::render('Community/TrailNews', [
            'title' => 'Kabar Jalur',
            ...$kabar->untuk($request->user()),
        ]);
    }
}
