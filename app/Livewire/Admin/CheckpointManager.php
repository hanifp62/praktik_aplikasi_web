<?php

namespace App\Livewire\Admin;

use App\Enums\CheckpointType;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Services\AuditLogService;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-16 checkpoint create/edit/reorder.
 */
#[Layout('layouts.app')]
#[Title('Kelola Checkpoint')]
class CheckpointManager extends Component
{
    public Trail $trail;

    public ?int $editingId = null;

    public string $name = '';

    public int $sequence = 1;

    public string $checkpoint_type = 'POS';

    public ?int $elevation_m = null;

    public ?string $notes = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public function mount(Trail $trail): void
    {
        $this->authorize('update', $trail);

        $this->trail = $trail;
        $this->sequence = ($trail->checkpoints()->max('sequence') ?? 0) + 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'sequence' => ['required', 'integer', 'min:1', 'max:200'],
            'checkpoint_type' => ['required', new Enum(CheckpointType::class)],
            'elevation_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function edit(int $id): void
    {
        $checkpoint = $this->trail->checkpoints()->findOrFail($id);

        $this->editingId = $checkpoint->id;
        $this->name = $checkpoint->name;
        $this->sequence = $checkpoint->sequence;
        $this->checkpoint_type = $checkpoint->checkpoint_type->value;
        $this->elevation_m = $checkpoint->elevation_m;
        $this->notes = $checkpoint->notes;

        // Dibaca dari kolom biasa; tidak perlu SQL spasial hanya untuk mengisi form.
        $this->latitude = $checkpoint->latitude;
        $this->longitude = $checkpoint->longitude;
    }

    public function save(AuditLogService $audit): void
    {
        $this->authorize('update', $this->trail);
        $this->validate();

        $attributes = [
            'trail_id' => $this->trail->id,
            'name' => $this->name,
            'sequence' => $this->sequence,
            'checkpoint_type' => $this->checkpoint_type,
            'elevation_m' => $this->elevation_m,
            'notes' => $this->notes,
        ];

        if ($this->editingId) {
            $checkpoint = Checkpoint::findOrFail($this->editingId);
            $checkpoint->update($attributes);
            $audit->recordChange(auth()->user(), 'checkpoint.updated', $checkpoint);
        } else {
            $checkpoint = Checkpoint::create($attributes);
            $audit->record(auth()->user(), 'checkpoint.created', $checkpoint, null, $attributes);
        }

        if ($this->latitude !== null && $this->longitude !== null) {
            $checkpoint->setCoordinates($this->latitude, $this->longitude);
        }

        $this->resetForm();
        session()->flash('status', 'Checkpoint tersimpan.');
    }

    public function move(int $id, int $direction, AuditLogService $audit): void
    {
        $this->authorize('update', $this->trail);

        $checkpoint = $this->trail->checkpoints()->findOrFail($id);
        $target = $this->trail->checkpoints()
            ->where('sequence', $direction < 0 ? '<' : '>', $checkpoint->sequence)
            ->orderBy('sequence', $direction < 0 ? 'desc' : 'asc')
            ->first();

        if ($target === null) {
            return;
        }

        // Park the moving row on a free sequence first: (trail_id, sequence) is unique.
        $parked = ($this->trail->checkpoints()->max('sequence') ?? 0) + 1;
        $original = $checkpoint->sequence;
        $targetSequence = $target->sequence;

        $checkpoint->update(['sequence' => $parked]);
        $target->update(['sequence' => $original]);
        $checkpoint->update(['sequence' => $targetSequence]);

        $audit->record(auth()->user(), 'checkpoint.reordered', $checkpoint, ['sequence' => $original], ['sequence' => $checkpoint->sequence]);
    }

    public function delete(int $id, AuditLogService $audit): void
    {
        $this->authorize('update', $this->trail);

        $checkpoint = $this->trail->checkpoints()->findOrFail($id);
        $audit->record(auth()->user(), 'checkpoint.deleted', $checkpoint, $checkpoint->toArray(), null);
        $checkpoint->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'elevation_m', 'notes', 'latitude', 'longitude']);
        $this->sequence = ($this->trail->checkpoints()->max('sequence') ?? 0) + 1;
    }

    public function render()
    {
        return view('livewire.admin.checkpoint-manager', [
            'checkpoints' => $this->trail->checkpoints()->get(),
            'types' => CheckpointType::cases(),
        ]);
    }
}
