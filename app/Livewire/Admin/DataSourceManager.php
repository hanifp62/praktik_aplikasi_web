<?php

namespace App\Livewire\Admin;

use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use App\Models\DataSource;
use App\Services\AuditLogService;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-18 data source registration and verification (PRD §58-60).
 */
#[Layout('layouts.app')]
#[Title('Kelola Sumber Data')]
class DataSourceManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $source_name = '';

    public string $source_type = 'OFFICIAL';

    public ?string $source_url = null;

    public ?string $source_owner = null;

    public ?string $freshness_policy = null;

    public ?string $notes = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'source_name' => ['required', 'string', 'max:160'],
            'source_type' => ['required', new Enum(SourceType::class)],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_owner' => ['nullable', 'string', 'max:160'],
            'freshness_policy' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function edit(int $id): void
    {
        $source = DataSource::findOrFail($id);
        $this->authorize('update', $source);

        $this->editingId = $source->id;
        $this->source_name = $source->source_name;
        $this->source_type = $source->source_type->value;
        $this->source_url = $source->source_url;
        $this->source_owner = $source->source_owner;
        $this->freshness_policy = $source->freshness_policy;
        $this->notes = $source->notes;
    }

    public function save(AuditLogService $audit): void
    {
        $this->validate();

        $attributes = [
            'source_name' => $this->source_name,
            'source_type' => $this->source_type,
            'source_url' => $this->source_url,
            'source_owner' => $this->source_owner,
            'freshness_policy' => $this->freshness_policy,
            'notes' => $this->notes,
            'retrieved_at' => now(),
        ];

        if ($this->editingId) {
            $source = DataSource::findOrFail($this->editingId);
            $this->authorize('update', $source);
            $source->update($attributes);
            $audit->recordChange(auth()->user(), 'data_source.updated', $source);
        } else {
            $this->authorize('create', DataSource::class);
            $source = DataSource::create($attributes);
            $audit->record(auth()->user(), 'data_source.created', $source, null, $attributes);
        }

        $this->reset(['editingId', 'source_name', 'source_url', 'source_owner', 'freshness_policy', 'notes']);
        session()->flash('status', 'Sumber data tersimpan.');
    }

    public function verify(int $id, AuditLogService $audit): void
    {
        $source = DataSource::findOrFail($id);
        $this->authorize('verify', $source);

        $source->update([
            'verification_status' => VerificationStatus::VERIFIED->value,
            'verified_at' => now(),
        ]);

        $audit->recordChange(auth()->user(), 'data_source.verified', $source);
    }

    public function dispute(int $id, AuditLogService $audit): void
    {
        $source = DataSource::findOrFail($id);
        $this->authorize('verify', $source);

        $source->update(['verification_status' => VerificationStatus::DISPUTED->value]);
        $audit->recordChange(auth()->user(), 'data_source.disputed', $source);
    }

    public function render()
    {
        return view('livewire.admin.data-source-manager', [
            'sources' => DataSource::query()->orderBy('source_name')->paginate(10),
            'types' => SourceType::cases(),
        ]);
    }
}
