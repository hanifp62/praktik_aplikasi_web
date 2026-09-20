<?php

namespace App\Livewire\Admin;

use App\Models\DataSource;
use App\Models\Mountain;
use App\Services\AuditLogService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-16 admin: mountain create/edit/archive with audit logging (PRD §61).
 */
#[Layout('layouts.app')]
#[Title('Kelola Gunung')]
class MountainManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $province = null;

    public ?string $region = null;

    public ?int $elevation_mdpl = null;

    public ?string $description = null;

    public ?int $data_source_id = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $showForm = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'elevation_mdpl' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'data_source_id' => ['nullable', Rule::exists('data_sources', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Mountain::class);

        $this->reset(['editingId', 'name', 'province', 'region', 'elevation_mdpl', 'description', 'data_source_id', 'latitude', 'longitude']);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $mountain = Mountain::findOrFail($id);
        $this->authorize('update', $mountain);

        $this->editingId = $mountain->id;
        $this->name = $mountain->name;
        $this->province = $mountain->province;
        $this->region = $mountain->region;
        $this->elevation_mdpl = $mountain->elevation_mdpl;
        $this->description = $mountain->description;
        $this->data_source_id = $mountain->data_source_id;

        // Dibaca dari kolom biasa: kolom geografi kosong pada koneksi tanpa PostGIS.
        $this->latitude = $mountain->latitude;
        $this->longitude = $mountain->longitude;

        $this->showForm = true;
    }

    public function save(AuditLogService $audit): void
    {
        $this->validate();

        $attributes = [
            'name' => $this->name,
            'slug' => Str::slug($this->name),
            'province' => $this->province,
            'region' => $this->region,
            'elevation_mdpl' => $this->elevation_mdpl,
            'description' => $this->description,
            'data_source_id' => $this->data_source_id,
        ];

        if ($this->editingId) {
            $mountain = Mountain::findOrFail($this->editingId);
            $this->authorize('update', $mountain);

            $mountain->update($attributes);
            $audit->recordChange(auth()->user(), 'mountain.updated', $mountain);
        } else {
            $this->authorize('create', Mountain::class);

            $mountain = Mountain::create($attributes);
            $audit->record(auth()->user(), 'mountain.created', $mountain, null, $attributes);
        }

        $mountain->setCoordinates($this->latitude, $this->longitude);

        $this->showForm = false;
        session()->flash('status', 'Data gunung tersimpan.');
    }

    public function toggleArchive(int $id, AuditLogService $audit): void
    {
        $mountain = Mountain::findOrFail($id);
        $this->authorize('archive', $mountain);

        $mountain->update(['archived_at' => $mountain->archived_at ? null : now()]);
        $audit->recordChange(auth()->user(), 'mountain.archive_toggled', $mountain);
    }

    public function render()
    {
        return view('livewire.admin.mountain-manager', [
            'mountains' => Mountain::query()->withCount('trails')->orderBy('name')->paginate(10),
            'sources' => DataSource::orderBy('source_name')->get(),
        ]);
    }
}
