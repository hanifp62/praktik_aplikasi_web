<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataSourceRequest;
use App\Http\Resources\Admin\DataSourceRowResource;
use App\Models\DataSource;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-18: pendaftaran dan verifikasi sumber data (PRD §58-60). Setiap data penting harus
 * dapat ditelusuri ke sumber, tipe sumber, waktu pengambilan, dan status verifikasinya.
 */
class DataSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DataSource::class);

        $sources = DataSource::query()->orderBy('source_name')->paginate(10);

        return Inertia::render('Admin/DataSources', [
            'title' => 'Kelola Sumber Data',
            'sources' => [
                'data' => DataSourceRowResource::collection($sources->getCollection())->resolve($request),
                'links' => array_map(
                    fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']],
                    $sources->linkCollection()->all(),
                ),
            ],
            'types' => array_map(fn (SourceType $type) => ['value' => $type->value, 'label' => $type->label()], SourceType::cases()),
        ]);
    }

    public function store(DataSourceRequest $request, AuditLogService $audit): RedirectResponse
    {
        $attributes = $this->atribut($request);

        $source = DataSource::create($attributes);
        $audit->record($request->user(), 'data_source.created', $source, null, $attributes);

        return back()->with('status', 'Sumber data tersimpan.');
    }

    public function update(DataSourceRequest $request, DataSource $source, AuditLogService $audit): RedirectResponse
    {
        $source->update($this->atribut($request));
        $audit->recordChange($request->user(), 'data_source.updated', $source);

        return back()->with('status', 'Sumber data tersimpan.');
    }

    public function verify(Request $request, DataSource $source, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('verify', $source);

        $source->update([
            'verification_status' => VerificationStatus::VERIFIED->value,
            'verified_at' => now(),
        ]);
        $audit->recordChange($request->user(), 'data_source.verified', $source);

        return back()->with('status', $source->source_name.' diverifikasi.');
    }

    public function dispute(Request $request, DataSource $source, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('verify', $source);

        $source->update(['verification_status' => VerificationStatus::DISPUTED->value]);
        $audit->recordChange($request->user(), 'data_source.disputed', $source);

        return back()->with('status', $source->source_name.' ditandai diperdebatkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(DataSourceRequest $request): array
    {
        $data = $request->validated();

        return [
            'source_name' => $data['source_name'],
            'source_type' => $data['source_type'],
            'source_url' => $data['source_url'] ?? null,
            'source_owner' => $data['source_owner'] ?? null,
            'freshness_policy' => $data['freshness_policy'] ?? null,
            'notes' => $data['notes'] ?? null,
            'retrieved_at' => now(),
        ];
    }
}
