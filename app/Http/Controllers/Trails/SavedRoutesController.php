<?php

namespace App\Http\Controllers\Trails;

use App\Enums\RouteFitLabel;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrailRowResource;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Services\ConsiderationService;
use App\Services\TrailFitService;
use App\Services\Trails\TautanBagikan;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar rute tersimpan, terbaru dulu, beserta catatan pribadinya (FR-22).
 *
 * Membaca simpanan di basis data lewat ConsiderationService::simpanan(), sumber yang sama
 * dengan hitungan Simpanan di kepala halaman dan pilihan bawaan halaman Banding. Simpanan
 * yang hidup di sesi hilang setiap kali pendaki keluar, karena Logout memanggil
 * Session::invalidate().
 *
 * Catatan hanya dikirim ke halaman ini, milik pemiliknya; halaman Banding dan tautan
 * bagikan tidak pernah membawanya. Batas catatan dikirim dari konstanta yang sama dengan
 * aturan validasinya, supaya hitungan di formulir tidak menyebut angka yang berbeda dari
 * yang ditolak server.
 *
 * Sejak W3 setiap kartu membawa label kecocokan dasar pembacanya (prototype 06), dinilai
 * sekaligus untuk seluruh simpanan (TrailFitService, batch), tanpa skor (BR-09).
 */
class SavedRoutesController extends Controller
{
    public function __invoke(Request $request, ConsiderationService $consideration, TrailFitService $fit, TautanBagikan $tautan): Response
    {
        $pembaca = $request->user();
        $simpanan = $consideration->simpanan($pembaca);
        $jalur = $simpanan->pluck('trail')->filter(fn (Trail $t) => $t->is_published)->values();
        $kecocokan = $pembaca->hasCompletedProfile() ? $fit->forTrails($pembaca, new EloquentCollection($jalur->all())) : [];

        return Inertia::render('Trails/Saved', [
            'title' => 'Simpanan',
            'trails' => $simpanan->map(fn (TrailConsideration $baris) => [
                ...(new TrailRowResource($baris->trail, $kecocokan[$baris->trail_id] ?? null, true))->resolve($request),
                'terbit' => $baris->trail->is_published,
                'catatan' => $baris->note,
            ])->values()->all(),
            'batas' => $consideration->batasSimpanan(),
            'batasBanding' => $consideration->batasBanding(),
            'batasCatatan' => TrailConsideration::CATATAN_MAKS,
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
            // Tautan bagikan yang masih berlaku, supaya pembuatnya dapat mencabut kapan saja
            // (FR-23), dan masa berlaku tautan baru dari config (T8-M7).
            'tautanAktif' => $tautan->ringkasAktif($pembaca),
            'hariBerlakuTautan' => $tautan->hariBerlaku(),
            'pengumuman' => session('pengumuman', ''),
        ]);
    }
}
