<?php

namespace App\Http\Controllers\Community;

use App\Enums\DataErrorState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreDataErrorReportRequest;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\OfficialStatusService;
use Illuminate\Http\RedirectResponse;

/**
 * "Laporkan kesalahan data" dari kartu status jalur dan gunung (spec v3 bagian 10.7; rencana
 * induk W8 butir 9; ruling W8-R17). Laporan masuk antrean cek ulang kurator kawasan itu dan
 * tidak pernah mengubah status yang tampil (R-010). Pelapor dari sesi, sasaran dari rute, dan
 * catatan status yang sedang terbaca dibaca server.
 */
class DataErrorReportController extends Controller
{
    public const TERKIRIM = 'Laporan terkirim. Kurator Memount mengecek ulang ke sumber resminya; status yang tampil tidak berubah karena laporan ini.';

    public function __construct(private readonly OfficialStatusService $status) {}

    public function storeForTrail(StoreDataErrorReportRequest $request, Trail $trail): RedirectResponse
    {
        abort_unless($trail->is_published && $trail->archived_at === null, 404);

        $trail->loadMissing('mountain');
        $catatan = OfficialStatusService::catatanPenentu(
            $this->status->currentFor($trail),
            $trail->mountain !== null ? $this->status->currentFor($trail->mountain) : null,
        );

        $this->simpan($request, $request->user(), $trail->mountain_id, $trail->id, $catatan);

        return back()->with('pengumuman', self::TERKIRIM);
    }

    public function storeForMountain(StoreDataErrorReportRequest $request, Mountain $mountain): RedirectResponse
    {
        abort_if($mountain->isArchived(), 404);

        $this->simpan($request, $request->user(), $mountain->id, null, $this->status->currentFor($mountain));

        return back()->with('pengumuman', self::TERKIRIM);
    }

    private function simpan(StoreDataErrorReportRequest $request, User $pelapor, int $gunung, ?int $jalur, ?OfficialStatus $catatan): void
    {
        $laporan = new DataErrorReport($request->validated());
        $laporan->forceFill([
            'user_id' => $pelapor->id,
            'mountain_id' => $gunung,
            'trail_id' => $jalur,
            'official_status_id' => $catatan?->id,
            'state' => DataErrorState::OPEN->value,
        ])->save();
    }
}
