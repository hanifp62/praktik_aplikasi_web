<?php

namespace App\Http\Controllers\Trails;

use App\Enums\ConsiderationOutcome;
use App\Http\Controllers\Controller;
use App\Models\Trail;
use App\Services\ConsiderationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Simpan atau hapus jalur dari simpanan, lalu kembali ke halaman asal.
 *
 * Hanya jalur terbit yang boleh disimpan: halaman banding hanya membandingkan jalur
 * terbit, jadi draft yang masuk simpanan dari sini akan lenyap tanpa kabar di sana.
 * Penolakan karena simpanan penuh dinyatakan lewat flash, bukan didiamkan; hasil simpan
 * diumumkan lewat wilayah role="status" di halaman.
 */
class ConsiderationController extends Controller
{
    public function toggle(Request $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $jalur = Trail::published()->findOrFail($trail);

        $hasil = $consideration->toggle($request->user(), $jalur);

        if ($hasil === ConsiderationOutcome::DITOLAK) {
            return back()->with(
                'timbangan-penuh',
                ConsiderationOutcome::DITOLAK->label().' ('.$consideration->batasSimpanan().' jalur). Hapus satu dulu sebelum menyimpan jalur lain.'
            );
        }

        return back()->with('pengumuman', $jalur->name.' '.Str::lcfirst($hasil->label()).'.');
    }

    /**
     * Menghapus dari simpanan, sumber yang sama dengan baki Jelajah dan halaman Banding.
     *
     * remove(), bukan toggle(): klik ganda atau ulang-kirim yang menemukan barisnya sudah
     * tidak ada tidak boleh menambahkannya kembali. Yang dihapus selalu baris milik
     * pengguna yang masuk, jadi id jalur dari klien tidak dapat menyentuh simpanan orang
     * lain.
     */
    public function destroy(Request $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $consideration->remove($request->user(), $trail);

        $nama = Trail::query()->whereKey($trail)->value('name');

        return back()->with(
            'pengumuman',
            $nama ? $nama.' '.Str::lcfirst(ConsiderationOutcome::DIHAPUS->label()).'.' : ''
        );
    }
}
