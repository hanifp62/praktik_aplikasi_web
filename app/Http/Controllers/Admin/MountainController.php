<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MountainRequest;
use App\Http\Resources\Admin\MountainRowResource;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-16 admin: tambah, ubah, dan arsipkan gunung dengan jejak audit (PRD §61).
 *
 * Tidak ada hapus. Gunung adalah induk jalur, trip, dan status resmi; mengarsipkan
 * menyembunyikannya dari pencarian tanpa memutus riwayat di bawahnya.
 */
class MountainController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Mountain::class);

        $mountains = Mountain::query()->withCount('trails')->orderBy('name')->paginate(10);

        return Inertia::render('Admin/Mountains', [
            'title' => 'Kelola Gunung',
            'mountains' => [
                'data' => MountainRowResource::collection($mountains->getCollection())->resolve($request),
                'links' => $this->tautanHalaman($mountains->linkCollection()->all()),
            ],
            'sources' => DataSource::query()->orderBy('source_name')->get(['id', 'source_name'])
                ->map(fn (DataSource $source) => ['value' => (string) $source->id, 'label' => $source->source_name])
                ->all(),
        ]);
    }

    public function store(MountainRequest $request, AuditLogService $audit): RedirectResponse
    {
        $attributes = $this->atribut($request);

        $mountain = Mountain::create($attributes);
        $audit->record($request->user(), 'mountain.created', $mountain, null, $attributes);

        $mountain->setCoordinates($this->koordinat($request, 'latitude'), $this->koordinat($request, 'longitude'));

        return back()->with('status', 'Data gunung tersimpan.');
    }

    public function update(MountainRequest $request, Mountain $mountain, AuditLogService $audit): RedirectResponse
    {
        $mountain->update($this->atribut($request));
        $audit->recordChange($request->user(), 'mountain.updated', $mountain);

        $mountain->setCoordinates($this->koordinat($request, 'latitude'), $this->koordinat($request, 'longitude'));

        return back()->with('status', 'Data gunung tersimpan.');
    }

    public function toggleArchive(Request $request, Mountain $mountain, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('archive', $mountain);

        $mountain->update(['archived_at' => $mountain->archived_at ? null : now()]);
        $audit->recordChange($request->user(), 'mountain.archive_toggled', $mountain);

        return back()->with('status', $mountain->name.($mountain->isArchived() ? ' diarsipkan.' : ' diaktifkan kembali.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(MountainRequest $request): array
    {
        $data = $request->validated();

        return [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'province' => $data['province'] ?? null,
            'region' => $data['region'] ?? null,
            'elevation_mdpl' => isset($data['elevation_mdpl']) ? (int) $data['elevation_mdpl'] : null,
            'description' => $data['description'] ?? null,
            'data_source_id' => isset($data['data_source_id']) ? (int) $data['data_source_id'] : null,
        ];
    }

    private function koordinat(MountainRequest $request, string $kolom): ?float
    {
        $nilai = $request->validated($kolom);

        return $nilai === null ? null : (float) $nilai;
    }

    /**
     * @param  array<int, array{url: string|null, label: string, active: bool}>  $links
     * @return array<int, array{url: string|null, label: string, active: bool}>
     */
    private function tautanHalaman(array $links): array
    {
        return array_map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']], $links);
    }
}
