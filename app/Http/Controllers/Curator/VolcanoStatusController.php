<?php

namespace App\Http\Controllers\Curator;

use App\Enums\VolcanoLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curator\SimpanStatusGunungApiRequest;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use App\Services\AuditLogService;
use App\Services\DataResmi\StatusGunungApi;
use App\Services\DataResmi\Yurisdiksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pencatatan status gunung api PVMBG di meja kurator (DK-43; spec v3 bagian 10.7; ruling W8-R18).
 * Kurator menyalin level, rekomendasi radius, dan tautan laporan dari MAGMA Indonesia, tanpa
 * scraping. Satu kurator cukup karena level bukan status akses; setiap pencatatan diaudit.
 */
class VolcanoStatusController extends Controller
{
    private const DIAUDIT = ['mountain_id', 'level', 'recommendation', 'report_url', 'issued_at', 'verified_at', 'recorded_by'];

    public function create(Request $request, Yurisdiksi $yurisdiksi, StatusGunungApi $gunungApi): Response
    {
        $this->authorize('viewAny', VolcanoStatus::class);

        $gunung = Mountain::query()->whereIn('id', $yurisdiksi->gunung($request->user())->all())->orderBy('name')->get(['id', 'name', 'timezone']);
        $terkini = $gunungApi->terkiniUntuk($gunung->pluck('id'));
        $awal = (string) $request->integer('gunung');

        return Inertia::render('Curator/VolcanoForm', [
            'title' => 'Catat status gunung api',
            'gunung' => $gunung->map(fn (Mountain $m) => [
                'value' => (string) $m->id,
                'label' => $m->name,
                'terkini' => isset($terkini[$m->id]) ? $terkini[$m->id]->level->label() : null,
            ])->values()->all(),
            'gunungAwal' => $gunung->contains('id', (int) $awal) ? $awal : null,
            'level' => VolcanoLevel::pilihan(),
            'atribusi' => VolcanoLevel::ATRIBUSI,
        ]);
    }

    public function store(SimpanStatusGunungApiRequest $request, AuditLogService $audit): RedirectResponse
    {
        $gunung = $request->gunung();
        $data = $request->validated();

        $catatan = new VolcanoStatus([
            'level' => (int) $data['level'],
            'recommendation' => $data['rekomendasi'],
            'report_url' => $data['tautan_laporan'],
            'issued_at' => $request->diterbitkanUtc($gunung),
        ]);
        $catatan->forceFill([
            'mountain_id' => $gunung->id,
            'recorded_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        DB::transaction(function () use ($catatan, $audit, $request): void {
            $catatan->save();
            $audit->record($request->user(), 'volcano_status.recorded', $catatan, null, $catatan->only(self::DIAUDIT));
        });

        return to_route('curator.desk')->with('status', 'Status gunung api '.$gunung->name.' dicatat: '.$catatan->level->label().'.');
    }
}
