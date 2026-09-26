<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrailRowResource;
use App\Models\TrailConsideration;
use App\Services\ConsiderationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar rute tersimpan, terbaru dulu, beserta catatan pribadinya (FR-22).
 *
 * Membaca simpanan di basis data lewat ConsiderationService::simpanan(), sumber yang sama
 * dengan baki di Jelajah dan pilihan bawaan halaman Banding. Simpanan yang hidup di sesi
 * hilang setiap kali pendaki keluar, karena Logout memanggil Session::invalidate().
 *
 * Catatan hanya dikirim ke halaman ini, milik pemiliknya; halaman Banding dan tautan
 * bagikan tidak pernah membawanya. Batas catatan dikirim dari konstanta yang sama dengan
 * aturan validasinya, supaya hitungan di formulir tidak menyebut angka yang berbeda dari
 * yang ditolak server.
 */
class SavedRoutesController extends Controller
{
    public function __invoke(Request $request, ConsiderationService $consideration): Response
    {
        $simpanan = $consideration->simpanan($request->user());

        return Inertia::render('Trails/Saved', [
            'title' => 'Rute Tersimpan',
            'trails' => $simpanan->map(fn (TrailConsideration $baris) => [
                ...(new TrailRowResource($baris->trail, null, true))->resolve($request),
                'terbit' => $baris->trail->is_published,
                'catatan' => $baris->note,
            ])->values()->all(),
            'batas' => $consideration->batasSimpanan(),
            'batasBanding' => $consideration->batasBanding(),
            'batasCatatan' => TrailConsideration::CATATAN_MAKS,
            'pengumuman' => session('pengumuman', ''),
        ]);
    }
}
