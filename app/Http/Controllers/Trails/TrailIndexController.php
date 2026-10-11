<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Services\Trails\HalamanJelajah;
use App\Services\Trails\TrailSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jelajah: telusur, tafsiran kalimat, dan saringan jalur (PRD §94, FR-27, spec v3 bagian 6.2).
 * Isinya disusun HalamanJelajah; controller ini hanya membaca permintaan dan merender.
 */
class TrailIndexController extends Controller
{
    public function __invoke(Request $request, HalamanJelajah $halaman): Response
    {
        return Inertia::render('Trails/Index', [
            'title' => 'Jelajahi Jalur',
            'pengumuman' => session('pengumuman', ''),
        ] + $halaman->props(TrailSearch::dariInput($request->query()), $request->user(), $request));
    }
}
