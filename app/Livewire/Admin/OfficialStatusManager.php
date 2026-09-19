<?php

namespace App\Livewire\Admin;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\AuditLogService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-11 official status metadata. Status can be scoped to a mountain, trail or segment
 * (PRD §42), and every record carries its source and timestamps (PRD §41).
 */
#[Layout('layouts.app')]
#[Title('Kelola Status Resmi')]
class OfficialStatusManager extends Component
{
    use WithPagination;

    public string $scope = 'TRAIL';

    public ?int $statusable_id = null;

    public string $status = 'UNKNOWN';

    public ?int $data_source_id = null;

    public ?string $source = null;

    public ?string $source_url = null;

    public ?string $published_at = null;

    public ?string $effective_at = null;

    public ?string $expires_at = null;

    public ?string $reason = null;

    public ?string $notes = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'scope' => ['required', new Enum(StatusScope::class)],
            'statusable_id' => ['required', 'integer'],
            'status' => ['required', new Enum(OfficialStatusValue::class)],
            'data_source_id' => ['nullable', Rule::exists('data_sources', 'id')],
            'source' => ['nullable', 'string', 'max:160'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'effective_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:effective_at'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return class-string
     */
    private function modelClassForScope(): string
    {
        return match (StatusScope::from($this->scope)) {
            StatusScope::MOUNTAIN => Mountain::class,
            StatusScope::SEGMENT => TrailSegment::class,
            default => Trail::class,
        };
    }

    public function save(AuditLogService $audit): void
    {
        $this->authorize('create', OfficialStatus::class);
        $this->validate();

        $modelClass = $this->modelClassForScope();
        $statusable = $modelClass::findOrFail($this->statusable_id);

        $record = OfficialStatus::create([
            'statusable_type' => $statusable->getMorphClass(),
            'statusable_id' => $statusable->getKey(),
            'scope' => $this->scope,
            'status' => $this->status,
            'data_source_id' => $this->data_source_id,
            'source' => $this->source,
            'source_url' => $this->source_url,
            'published_at' => $this->published_at,
            'fetched_at' => now(),
            'verified_at' => now(),
            'effective_at' => $this->effective_at ?? now(),
            'expires_at' => $this->expires_at,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'recorded_by' => auth()->id(),
        ]);

        $audit->record(auth()->user(), 'official_status.recorded', $record, null, $record->toArray());

        $this->reset(['statusable_id', 'source', 'source_url', 'published_at', 'effective_at', 'expires_at', 'reason', 'notes']);
        session()->flash('status', 'Status resmi tercatat.');
    }

    public function render()
    {
        $targets = match (StatusScope::from($this->scope)) {
            StatusScope::MOUNTAIN => Mountain::active()->orderBy('name')->get()
                ->mapWithKeys(fn ($item) => [$item->id => $item->name]),
            StatusScope::SEGMENT => TrailSegment::with('trail')->orderBy('name')->get()
                ->mapWithKeys(fn ($item) => [$item->id => $item->trail->name.' - '.$item->name]),
            default => Trail::active()->with('mountain')->orderBy('name')->get()
                ->mapWithKeys(fn ($item) => [$item->id => $item->name.' - '.$item->mountain->name]),
        };

        return view('livewire.admin.official-status-manager', [
            'targets' => $targets->all(),
            'scopes' => StatusScope::cases(),
            'statuses' => OfficialStatusValue::cases(),
            'sources' => DataSource::orderBy('source_name')->get(),
            'records' => OfficialStatus::query()->with('statusable', 'recordedBy')
                ->orderByDesc('created_at')->paginate(10),
        ]);
    }
}
