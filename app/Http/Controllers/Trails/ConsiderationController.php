<?php

namespace App\Http\Controllers\Trails;

use App\Enums\ConsiderationOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\StoreSavedTrailRequest;
use App\Models\Trail;
use App\Models\TrailConsideration;
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
    /** Awalan kunci sesi penanda pemulihan; diikuti id jalur. */
    private const KUNCI_PEMULIHAN = 'simpanan-pulihkan.';

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
     * Menghapus dari simpanan, sumber yang sama dengan hitungan Simpanan dan halaman Banding.
     *
     * remove(), bukan toggle(): klik ganda atau ulang-kirim yang menemukan barisnya sudah
     * tidak ada tidak boleh menambahkannya kembali. Yang dihapus selalu baris milik
     * pengguna yang masuk, jadi id jalur dari klien tidak dapat menyentuh simpanan orang
     * lain.
     *
     * Urungkan dan nama jalur hanya untuk penghapusan yang benar-benar terjadi (m4 tinjauan
     * akhir W3): tanpa baris yang terhapus tidak ada yang perlu dipulihkan, dan nama jalur
     * yang tidak pernah ada di simpanan pembaca (misalnya draf) tidak disebut.
     */
    public function destroy(Request $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        // Catatan dibaca sebelum barisnya dihapus, lalu dititipkan satu kali di sesi pemiliknya
        // sendiri, supaya Urungkan dapat memulihkannya utuh (spec v3 bagian 9.1).
        $catatan = TrailConsideration::query()
            ->where('user_id', $request->user()->id)
            ->where('trail_id', $trail)
            ->value('note');

        if ($consideration->remove($request->user(), $trail) === 0) {
            return back()->with('pengumuman', 'Jalur ini sudah tidak ada di simpanan.');
        }

        // Penanda pemulihan sekali-pakai (C1 tinjauan akhir W3, ruling W3-F2). Bukan flash:
        // flash habis pada kunjungan yang menampilkan toast, sebelum POST Urungkan tiba.
        // Nilainya id pemilik, supaya sesi yang berganti akun tidak mewarisinya.
        $request->session()->put(self::KUNCI_PEMULIHAN.$trail, $request->user()->id);

        $nama = Trail::query()->whereKey($trail)->value('name');

        return back()
            ->with('pengumuman', $nama.' '.Str::lcfirst(ConsiderationOutcome::DIHAPUS->label()).'.')
            ->with('simpanan-terhapus', ['trailId' => $trail, 'nama' => $nama, 'catatan' => $catatan]);
    }

    /**
     * Simpan idempoten: tombol simpan kartu dan bar aksi, dan Urungkan sesudah "Hapus dari
     * simpanan" yang mengirim kembali catatannya. Hanya jalur terbit, sama dengan toggle();
     * simpanan yang penuh ditolak dengan pesan yang sama. Baris yang sudah ada (misalnya
     * dari tab atau perangkat lain) di-flash terpisah lewat simpanan-sudah-ada, supaya
     * klien tidak menawarkan Urungkan-sebagai-hapus untuk baris yang tidak dibuatnya
     * (tinjauan Tugas 4 temuan 2; ruling W3-4b).
     *
     * Satu pengecualian untuk "hanya jalur terbit": Urungkan sesudah pemiliknya menghapus jalur
     * yang sudah ditarik dari terbit (masih tampil di Simpanan bersama catatannya). Untuk itu
     * destroy() menitipkan penanda pemulihan di sesi pemiliknya; bila penanda untuk jalur ini
     * ada dan milik pembaca, jalur aktif (belum diarsip) diterima dan penandanya dibuang, jadi
     * hanya berlaku sekali (C1 tinjauan akhir W3, ruling W3-F2). Jalur arsip tetap 404.
     */
    public function store(StoreSavedTrailRequest $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $kunci = self::KUNCI_PEMULIHAN.$trail;
        $pulihkan = $request->session()->get($kunci) === $request->user()->id;

        if ($pulihkan) {
            $request->session()->forget($kunci);
        }

        $jalur = ($pulihkan ? Trail::active() : Trail::published())->findOrFail($trail);

        $hasil = $consideration->simpan($request->user(), $jalur, $request->validated('catatan'));

        if ($hasil === ConsiderationOutcome::DITOLAK) {
            return back()->with(
                'timbangan-penuh',
                ConsiderationOutcome::DITOLAK->label().' ('.$consideration->batasSimpanan().' jalur). Hapus satu dulu sebelum menyimpan jalur lain.'
            );
        }

        if ($hasil === ConsiderationOutcome::SUDAH_ADA) {
            return back()
                ->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::SUDAH_ADA->label()).'.')
                ->with('simpanan-sudah-ada', true);
        }

        return back()->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::DITAMBAHKAN->label()).'.');
    }
}
