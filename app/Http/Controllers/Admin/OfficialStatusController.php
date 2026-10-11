<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficialStatusRequest;
use App\Http\Resources\Admin\OfficialStatusRowResource;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\AuditLogService;
use App\Services\DataFreshnessService;
use App\Support\PaginasiInertia;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-11 status resmi. Status melekat pada gunung, jalur, atau segmen (PRD §42) dan
 * setiap catatan membawa sumber dan waktunya (PRD §41). Hierarki otoritas R-009 tidak
 * disentuh di sini: yang dicatat hanya OFFICIAL, dan laporan komunitas tidak pernah
 * lewat halaman ini.
 */
class OfficialStatusController extends Controller
{
    public function index(Request $request, DataFreshnessService $kesegaran): Response
    {
        $this->authorize('viewAny', OfficialStatus::class);

        // Ketiga daftar sasaran dikirim sekaligus supaya pergantian cakupan di form tidak
        // butuh kunjungan server; jumlah query-nya tetap, tidak ikut jumlah data.
        $sasaran = [
            StatusScope::MOUNTAIN->value => Mountain::active()->orderBy('name')->get()
                ->map(fn (Mountain $m) => ['value' => $m->id, 'label' => $m->name])->values()->all(),
            StatusScope::SEGMENT->value => TrailSegment::with('trail')->orderBy('name')->get()
                ->map(fn (TrailSegment $s) => ['value' => $s->id, 'label' => $s->trail->name.' - '.$s->name])->values()->all(),
            StatusScope::TRAIL->value => Trail::active()->with('mountain')->orderBy('name')->get()
                ->map(fn (Trail $t) => ['value' => $t->id, 'label' => $t->name.' - '.$t->mountain->name])->values()->all(),
        ];
        $sasaran[StatusScope::AREA->value] = $sasaran[StatusScope::TRAIL->value];

        $tinjau = $kesegaran->review();

        return Inertia::render('Admin/OfficialStatuses', [
            'title' => 'Kelola Status Resmi',
            'scopes' => array_map(fn (StatusScope $s) => ['value' => $s->value, 'label' => $s->label()], StatusScope::cases()),
            'statuses' => array_map(fn (OfficialStatusValue $s) => ['value' => $s->value, 'label' => $s->label()], OfficialStatusValue::cases()),
            'sources' => DataSource::orderBy('source_name')->get()
                ->map(fn (DataSource $d) => ['value' => $d->id, 'label' => $d->source_name])->values()->all(),
            'targets' => $sasaran,
            'tinjau' => [
                'kedaluwarsa' => $tinjau['kedaluwarsa']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'kedaluwarsa'))->values()->all(),
                'segera' => $tinjau['segera']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'segera'))->values()->all(),
                'basi' => $tinjau['basi']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'basi'))->values()->all(),
            ],
            'records' => PaginasiInertia::props(
                OfficialStatus::query()->with('statusable', 'recordedBy', 'dataSource')->orderByDesc('created_at')->paginate(10)->withQueryString(),
                fn (OfficialStatus $s) => (new OfficialStatusRowResource($s))->resolve($request),
            ),
        ]);
    }

    public function store(StoreOfficialStatusRequest $request, AuditLogService $audit): RedirectResponse
    {
        $data = $request->validated();
        $modelClass = $request->modelClassForScope();
        $statusable = $modelClass::findOrFail($data['statusable_id']);

        $record = OfficialStatus::create([
            'statusable_type' => $statusable->getMorphClass(),
            'statusable_id' => $statusable->getKey(),
            'scope' => $data['scope'],
            'status' => $data['status'],
            'data_source_id' => $data['data_source_id'] ?? null,
            'source' => $data['source'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'published_at' => $data['published_at'] ?? null,
            'fetched_at' => now(),
            'verified_at' => now(),
            'effective_at' => $data['effective_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        $audit->record($request->user(), 'official_status.recorded', $record, null, $record->toArray());

        return redirect()->route('admin.statuses')->with('status', 'Status resmi tercatat.');
    }

    /**
     * Tanggal dari instan UTC ditulis sebagai tanggal kalender WIB (Timezone::DEFAULT, ruling FF-1), sama dengan
     * OfficialStatusRowResource: halaman admin tidak berkonteks satu jalur.
     *
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
            // Untuk tombol "Catat ulang": mengisi form dengan sasaran yang sama.
            'scope' => $s->scope->value,
            'statusableId' => (string) $s->statusable_id,
        ];
    }
}
