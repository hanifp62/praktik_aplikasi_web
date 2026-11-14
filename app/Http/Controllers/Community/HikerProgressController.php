<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Trail;
use App\Services\Community\HubProgres;
use App\Services\Trails\CariJalur;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Progres pendaki, dipantulkan kembali kepadanya sendiri (R-015; spec v3 bagian 6.14). Isinya
 * milik HubProgres; `?tahun=` memilih tahun yang dibuka. Hasil pencarian jalur katalog untuk
 * formulir catatan manual hanya dikirim lewat muat ulang parsial, saat pendaki mencari.
 */
class HikerProgressController extends Controller
{
    public function __invoke(Request $request, HubProgres $hub, CariJalur $cari): Response
    {
        $diminta = $request->integer('tahun');
        $kata = $request->query('cari');

        return Inertia::render('Community/HikerProgress', [
            'title' => 'Progres Pendakian',
            ...$hub->untuk($request->user(), $diminta > 0 ? $diminta : null, $request->session()->get('kabar-progres')),
            'pilihanJalur' => Inertia::optional(fn () => $cari->untuk(is_string($kata) ? $kata : '')->map(fn (Trail $t) => CariJalur::baris($t))->all()),
        ]);
    }
}
