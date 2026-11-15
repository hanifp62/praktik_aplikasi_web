<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Curator\PersoalkanStatusRequest;
use App\Http\Requests\Curator\SimpanCatatanStatusRequest;
use App\Models\OfficialStatus;
use App\Services\DataResmi\HalamanCatatanStatus;
use App\Services\DataResmi\TerbitStatus;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catatan status resmi di meja kurator: formulir (masukan dan penstrukturan), rincian
 * (pemeriksaan konsistensi, verifikasi, terbit), dan aksinya (spec v3 bagian 10.7; ruling W8-R15).
 * Setiap aksi tulis memanggil layanan TerbitStatus, yang menegakkan aturan dua orang, aturan
 * terbit asimetris, dan Policy; controller hanya memilih halaman dan kalimat kabarnya.
 */
class CuratorStatusController extends Controller
{
    public function create(Request $request, HalamanCatatanStatus $halaman): Response
    {
        $this->authorize('viewDesk', OfficialStatus::class);

        return Inertia::render('Curator/StatusForm', $halaman->formulir($request->user(), null, $request->string('sasaran')->toString() ?: null));
    }

    public function store(SimpanCatatanStatusRequest $request, TerbitStatus $terbit): RedirectResponse
    {
        $sasaran = $request->sasaran();
        $draf = $terbit->buatDraf($request->user(), $sasaran, $request->otoritas(), $request->kolom($sasaran), $request->file('bukti'));

        return redirect()->route('curator.statuses.show', $draf)->with('status', 'Draf tersimpan. Periksa temuan pemeriksaan sebelum mengirim.');
    }

    public function show(Request $request, OfficialStatus $status, HalamanCatatanStatus $halaman): Response
    {
        $this->authorize('view', $status);

        return Inertia::render('Curator/Status', $halaman->rincian($request->user(), $status));
    }

    public function edit(Request $request, OfficialStatus $status, HalamanCatatanStatus $halaman): Response
    {
        $this->authorize('updateDraft', $status);

        return Inertia::render('Curator/StatusForm', $halaman->formulir($request->user(), $status, null));
    }

    public function update(SimpanCatatanStatusRequest $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->ubahDraf($request->user(), $status, $request->kolom($status->statusable), $request->file('bukti'));

        return redirect()->route('curator.statuses.show', $status)->with('status', 'Draf diperbarui.');
    }

    public function destroy(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->hapusDraf($request->user(), $status);

        return redirect()->route('curator.desk')->with('status', 'Draf dihapus beserta salinan buktinya.');
    }

    public function submit(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terkirim = $terbit->kirim($request->user(), $status);

        return redirect()->route('curator.statuses.show', $terkirim)->with('status', $terkirim->terlihatPendaki()
            ? 'Terbit dan terbaca pendaki, bertanda menunggu verifikasi kedua.'
            : 'Terkirim. Pendaki membacanya sesudah kurator kedua memverifikasi.');
    }

    public function verify(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->verifikasi($request->user(), $status);

        return redirect()->route('curator.statuses.show', $status)->with('status', 'Terverifikasi dan terbaca pendaki.');
    }

    public function dispute(PersoalkanStatusRequest $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->persoalkan($request->user(), $status, $request->validated('alasan'));

        return redirect()->route('curator.statuses.show', $status)->with('status', $status->fresh()->terlihatPendaki()
            ? 'Ditandai dipersoalkan. Pembatasan ini tetap terbaca pendaki sampai catatan penggantinya terbit.'
            : 'Ditandai dipersoalkan dan tidak lagi terbaca pendaki.');
    }

    public function review(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $diperiksa = $terbit->tinjauUlang($request->user(), $status);

        return redirect()->route('curator.statuses.show', $diperiksa)->with('status', 'Dicek ulang ke sumbernya. Tinjau berikutnya '
            .Timezone::display($diperiksa->next_review_at, HalamanCatatanStatus::zona($diperiksa->statusable), 'j M Y H.i').'.');
    }
}
