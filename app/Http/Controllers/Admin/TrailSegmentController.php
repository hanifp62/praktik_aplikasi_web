<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrailSegmentRequest;
use App\Http\Resources\Admin\TrailSegmentRowResource;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-16 dan PRD §92: tambah, ubah, urutkan, dan hapus segmen satu jalur.
 *
 * Terbuka bagi admin dan pemandu bersertifikat jenjang Ahli untuk kawasannya sendiri
 * lewat TrailPolicy::update. Segmen pada rute aksi diikat ke jalurnya (scopeBindings),
 * sehingga izin pada satu jalur tidak dapat menyentuh segmen jalur lain.
 */
class TrailSegmentController extends Controller
{
    public function index(Request $request, Trail $trail): Response
    {
        $this->authorize('update', $trail);

        $segments = $trail->segments()->withCount('checkpoints')->get();

        return Inertia::render('Admin/TrailSegments', [
            'title' => 'Kelola Segmen Jalur',
            'trail' => ['id' => $trail->id, 'name' => $trail->name],
            'segments' => TrailSegmentRowResource::collection($segments)->resolve($request),
            'nextSequence' => (string) (((int) $segments->max('sequence')) + 1),
            'demands' => array_map(fn (TechnicalDemand $d) => ['value' => $d->value, 'label' => $d->label()], TechnicalDemand::cases()),
            'terrains' => array_map(fn (TerrainCharacter $t) => ['value' => $t->value, 'label' => $t->label()], TerrainCharacter::cases()),
        ]);
    }

    public function store(TrailSegmentRequest $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        $attributes = $this->atribut($request, $trail);

        $segment = TrailSegment::create($attributes);
        $audit->record($request->user(), 'trail_segment.created', $segment, null, $attributes);

        return back()->with('status', 'Segmen jalur tersimpan.');
    }

    public function update(TrailSegmentRequest $request, Trail $trail, TrailSegment $segment, AuditLogService $audit): RedirectResponse
    {
        $segment->update($this->atribut($request, $trail));
        $audit->recordChange($request->user(), 'trail_segment.updated', $segment);

        return back()->with('status', 'Segmen jalur tersimpan.');
    }

    public function move(Request $request, Trail $trail, TrailSegment $segment, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $trail);

        $arah = (int) $request->validate(['arah' => ['required', 'integer', Rule::in([-1, 1])]])['arah'];

        $target = $trail->segments()
            ->where('sequence', $arah < 0 ? '<' : '>', $segment->sequence)
            ->reorder('sequence', $arah < 0 ? 'desc' : 'asc')
            ->first();

        if ($target === null) {
            return back();
        }

        $semula = $segment->sequence;

        DB::transaction(function () use ($trail, $segment, $target, $semula): void {
            $parkir = ((int) $trail->segments()->max('sequence')) + 1;
            $tujuan = $target->sequence;

            $segment->update(['sequence' => $parkir]);
            $target->update(['sequence' => $semula]);
            $segment->update(['sequence' => $tujuan]);
        });

        $audit->record($request->user(), 'trail_segment.reordered', $segment, ['sequence' => $semula], ['sequence' => $segment->sequence]);

        return back();
    }

    public function destroy(Request $request, Trail $trail, TrailSegment $segment, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $trail);

        $sebelum = $segment->toArray();

        DB::transaction(function () use ($segment): void {
            $segment->checkpoints()->update(['trail_segment_id' => null]);
            $segment->delete();
        });

        $audit->record($request->user(), 'trail_segment.deleted', $segment, $sebelum, null);

        return back()->with('status', 'Segmen jalur dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(TrailSegmentRequest $request, Trail $trail): array
    {
        $data = $request->validated();

        return [
            'trail_id' => $trail->id,
            'name' => $data['name'],
            'sequence' => (int) $data['sequence'],
            'description' => $data['description'] ?? null,
            'distance_km' => isset($data['distance_km']) && $data['distance_km'] !== '' ? (float) $data['distance_km'] : null,
            'elevation_gain_m' => isset($data['elevation_gain_m']) && $data['elevation_gain_m'] !== '' ? (int) $data['elevation_gain_m'] : null,
            'technical_demand' => ! empty($data['technical_demand']) ? $data['technical_demand'] : null,
            'terrain_character' => array_values($data['terrain_character'] ?? []),
        ];
    }
}
