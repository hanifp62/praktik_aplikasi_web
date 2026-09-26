<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\UpdateSavedTrailNoteRequest;
use App\Services\ConsiderationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Catatan pribadi pada jalur tersimpan (FR-22).
 *
 * Barisnya dicari di dalam simpanan pengguna yang masuk
 * (ConsiderationService::simpananMilik()), jadi id jalur dari klien tidak pernah
 * menunjuk baris orang lain: baris pendaki lain dan jalur yang belum disimpan sama-sama
 * 404. TrailConsiderationPolicy menjaga lapis kedua. Pemilik datang dari sesi dan jalur
 * dari rute; field lain di badan permintaan diabaikan karena hanya `catatan` yang
 * divalidasi. Hasilnya diumumkan lewat flash `pengumuman` yang dibaca wilayah
 * role="status" di halaman Simpanan.
 */
class SavedTrailNoteController extends Controller
{
    public function update(UpdateSavedTrailNoteRequest $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $simpanan = $consideration->simpananMilik($request->user(), $trail);
        $this->authorize('update', $simpanan);

        $catatan = $consideration->simpanCatatan($simpanan, $request->validated('catatan'));

        return back()->with(
            'pengumuman',
            'Catatan untuk '.$simpanan->trail->name.($catatan === null ? ' dihapus.' : ' disimpan.')
        );
    }

    public function destroy(Request $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $simpanan = $consideration->simpananMilik($request->user(), $trail);
        $this->authorize('update', $simpanan);

        $consideration->simpanCatatan($simpanan, null);

        return back()->with('pengumuman', 'Catatan untuk '.$simpanan->trail->name.' dihapus.');
    }
}
