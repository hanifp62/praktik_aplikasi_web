<?php

namespace App\Http\Controllers\Curator;

use App\Enums\DataErrorState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curator\CekLaporanKesalahanRequest;
use App\Models\DataErrorReport;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Menandai laporan kesalahan data sudah dicek (spec v3 bagian 10.7 langkah 7; ruling W8-R17).
 * Hanya menutup tugas cek ulang beserta hasilnya; status resmi tidak disentuh. Bila sumbernya
 * memang berubah, kurator mencatat status pengganti lewat formulir meja kurator.
 */
class DataErrorCheckController extends Controller
{
    public function __invoke(CekLaporanKesalahanRequest $request, DataErrorReport $laporan, AuditLogService $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $laporan, $audit): void {
            $sebelum = $laporan->only(['state', 'checked_by', 'checked_at', 'resolution']);
            $laporan->forceFill([
                'state' => DataErrorState::CHECKED->value,
                'checked_by' => $request->user()->id,
                'checked_at' => now(),
                'resolution' => $request->validated('hasil'),
            ])->save();
            $audit->record($request->user(), 'data_error_report.checked', $laporan, $sebelum, $laporan->only(['state', 'checked_by', 'checked_at', 'resolution']));
        });

        return to_route('curator.desk')->with('status', 'Laporan ditandai sudah dicek.');
    }
}
