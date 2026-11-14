<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OfficialStatusRowResource;
use App\Models\OfficialStatus;
use App\Services\DataFreshnessService;
use App\Support\PaginasiInertia;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-11 status resmi, halaman admin: riwayat seluruh catatan dan antrean tinjau (PRD §41, §93).
 *
 * Sejak W8 status resmi dicatat di meja kurator (spec v3 bagian 10.7; ruling W8-R16), tempat
 * aturan dua orang dan aturan terbit asimetris ditegakkan layanan TerbitStatus. Formulir pencatatan
 * admin dipensiunkan supaya tidak ada jalan tulis yang melewati aturan itu; admin memberi peran
 * kurator lewat keanggotaan badan resmi dan tetap melihat riwayat serta antrean di sini.
 */
class OfficialStatusController extends Controller
{
    public function index(Request $request, DataFreshnessService $kesegaran): Response
    {
        $this->authorize('viewAny', OfficialStatus::class);

        $tinjau = $kesegaran->review();

        return Inertia::render('Admin/OfficialStatuses', [
            'title' => 'Kelola Status Resmi',
            'tinjau' => [
                'kedaluwarsa' => $tinjau['kedaluwarsa']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'kedaluwarsa'))->values()->all(),
                'segera' => $tinjau['segera']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'segera'))->values()->all(),
                'basi' => $tinjau['basi']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'basi'))->values()->all(),
            ],
            'records' => PaginasiInertia::props(
                OfficialStatus::query()->with('statusable', 'recordedBy', 'dataSource')->orderByDesc('created_at')->paginate(10)->withQueryString(),
                fn (OfficialStatus $s) => (new OfficialStatusRowResource($s))->resolve($request),
            ),
            'badanUrl' => route('admin.authorities'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function barisTinjau(OfficialStatus $s, string $kategori): array
    {
        $keterangan = $kategori === 'basi'
            ? ($s->verified_at
                ? 'terakhir diverifikasi '.$s->verified_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('d M Y')
                : 'belum pernah diverifikasi')
            : 'berlaku sampai '.$s->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('d M Y');

        return [
            'id' => $s->id,
            'nama' => $s->statusable?->name ?? 'Objek terhapus',
            'status' => $s->status->value,
            'statusText' => $s->status->label(),
            'keterangan' => $keterangan,
        ];
    }
}
