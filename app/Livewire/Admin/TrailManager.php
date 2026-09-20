<?php

namespace App\Livewire\Admin;

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\Trail;
use App\Services\AuditLogService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Kelola Jalur')]
class TrailManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $mountain_id = null;

    public string $name = '';

    public ?string $description = null;

    public ?float $distance_km = null;

    public ?int $elevation_gain_m = null;

    public ?int $elevation_loss_m = null;

    public ?int $estimated_duration_minutes = null;

    public string $technical_demand = 'MODERATE';

    public string $navigation_complexity = 'MODERATE';

    public string $water_availability = 'UNKNOWN';

    /** @var array<int, string> */
    public array $terrain_character = [];

    public bool $camping_available = false;

    public ?string $starting_point = null;

    public ?string $weather_adm4_code = null;

    public ?string $weather_reference_area = null;

    public ?int $data_source_id = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'mountain_id' => ['required', 'exists:mountains,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'elevation_loss_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:20160'],
            'technical_demand' => ['required', new Enum(TechnicalDemand::class)],
            'navigation_complexity' => ['required', new Enum(NavigationComplexity::class)],
            'water_availability' => ['required', new Enum(WaterAvailability::class)],
            'terrain_character' => ['array'],
            'terrain_character.*' => [Rule::enum(TerrainCharacter::class)],
            'camping_available' => ['boolean'],
            'starting_point' => ['nullable', 'string', 'max:160'],
            'weather_adm4_code' => ['nullable', 'string', 'max:40'],
            'weather_reference_area' => ['nullable', 'string', 'max:160'],
            'data_source_id' => ['nullable', Rule::exists('data_sources', 'id')],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Trail::class);

        $this->reset([
            'editingId', 'name', 'description', 'distance_km', 'elevation_gain_m', 'elevation_loss_m',
            'estimated_duration_minutes', 'terrain_character', 'camping_available', 'starting_point',
            'weather_adm4_code', 'weather_reference_area', 'data_source_id',
        ]);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $trail = Trail::findOrFail($id);
        $this->authorize('update', $trail);

        $this->editingId = $trail->id;
        $this->mountain_id = $trail->mountain_id;
        $this->name = $trail->name;
        $this->description = $trail->description;
        $this->distance_km = $trail->distance_km !== null ? (float) $trail->distance_km : null;
        $this->elevation_gain_m = $trail->elevation_gain_m;
        $this->elevation_loss_m = $trail->elevation_loss_m;
        $this->estimated_duration_minutes = $trail->estimated_duration_minutes;
        $this->technical_demand = $trail->technical_demand->value;
        $this->navigation_complexity = $trail->navigation_complexity->value;
        $this->water_availability = $trail->water_availability->value;
        $this->terrain_character = $trail->terrain_character ?? [];
        $this->camping_available = $trail->camping_available;
        $this->starting_point = $trail->starting_point;
        $this->weather_adm4_code = $trail->weather_adm4_code;
        $this->weather_reference_area = $trail->weather_reference_area;
        $this->data_source_id = $trail->data_source_id;
        $this->showForm = true;
    }

    public function save(AuditLogService $audit): void
    {
        $this->validate();

        $attributes = [
            'mountain_id' => $this->mountain_id,
            'name' => $this->name,
            'slug' => Str::slug($this->name.'-'.$this->mountain_id),
            'description' => $this->description,
            'distance_km' => $this->distance_km,
            'elevation_gain_m' => $this->elevation_gain_m,
            'elevation_loss_m' => $this->elevation_loss_m,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'technical_demand' => $this->technical_demand,
            'navigation_complexity' => $this->navigation_complexity,
            'water_availability' => $this->water_availability,
            'terrain_character' => $this->terrain_character,
            'camping_available' => $this->camping_available,
            'starting_point' => $this->starting_point,
            'weather_adm4_code' => $this->weather_adm4_code,
            'weather_reference_area' => $this->weather_reference_area,
            'data_source_id' => $this->data_source_id,
        ];

        if ($this->editingId) {
            $trail = Trail::findOrFail($this->editingId);
            $this->authorize('update', $trail);
            $trail->update($attributes);
            $audit->recordChange(auth()->user(), 'trail.updated', $trail);
        } else {
            $this->authorize('create', Trail::class);
            $trail = Trail::create($attributes);
            $audit->record(auth()->user(), 'trail.created', $trail, null, $attributes);
        }

        $this->showForm = false;
        session()->flash('status', 'Data jalur tersimpan.');
    }

    public function togglePublish(int $id, AuditLogService $audit): void
    {
        $trail = Trail::findOrFail($id);

        if ($trail->is_published) {
            $this->authorize('update', $trail);
            $trail->update(['is_published' => false]);
            $audit->recordChange(auth()->user(), 'trail.unpublished', $trail);

            return;
        }

        // PRD §110: publishing is blocked until the minimum data quality set exists.
        $missing = $trail->publishabilityReport();

        if ($missing !== []) {
            session()->flash('status', 'Jalur belum dapat dipublikasikan. '.implode(' ', $missing));

            return;
        }

        $this->authorize('publish', $trail);
        $trail->update(['is_published' => true]);
        $audit->recordChange(auth()->user(), 'trail.published', $trail);
    }

    public function toggleArchive(int $id, AuditLogService $audit): void
    {
        $trail = Trail::findOrFail($id);
        $this->authorize('archive', $trail);

        $trail->update(['archived_at' => $trail->archived_at ? null : now()]);
        $audit->recordChange(auth()->user(), 'trail.archive_toggled', $trail);
    }

    public function render()
    {
        return view('livewire.admin.trail-manager', [
            'trails' => Trail::query()->with('mountain')->withCount(['checkpoints', 'officialStatuses'])->orderBy('name')->paginate(10),
            'mountains' => Mountain::active()->orderBy('name')->get(),
            'sources' => DataSource::orderBy('source_name')->get(),
            'technicalLevels' => TechnicalDemand::cases(),
            'navigationLevels' => NavigationComplexity::cases(),
            'waterLevels' => WaterAvailability::cases(),
            'terrainOptions' => TerrainCharacter::cases(),
        ]);
    }
}
