<?php

namespace App\Livewire\Contribute;

use App\Enums\NavigationComplexity;
use App\Enums\SourceType;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
use App\Models\DataSource;
use App\Models\ExpertCredential;
use App\Models\Trail;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman tempat pemandu bersertifikat mengisi data jalur di kawasan yang disahkan
 * untuknya (PRD §43, §109).
 *
 * Sampai sekarang haknya sudah ada tetapi pintunya belum: policy mengizinkan, namun
 * tidak ada halaman tempat mengisi. Ini pintunya.
 *
 * Yang sengaja TIDAK ada di sini: menerbitkan, mengarsipkan, dan membuat jalur baru.
 * Ahli menyiapkan datanya, admin memutuskan data itu layak dilihat pendaki. Gerbang
 * §110 tidak berpindah tangan.
 */
#[Layout('layouts.app')]
#[Title('Kontribusi Data Jalur')]
class TrailContribution extends Component
{
    public ?int $editingId = null;

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

    public ?string $description = null;

    public ?string $catatan_sumber = null;

    public function mount(): void
    {
        abort_unless($this->kredensialAktif()->isNotEmpty(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
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
            'description' => ['nullable', 'string', 'max:2000'],

            // Wajib. Angka tanpa asal tidak dapat dinilai pembacanya (§60), dan di sini
            // asalnya adalah pengetahuan lapangan seseorang, bukan dokumen yang dapat
            // ditelusuri sendiri oleh pembaca.
            'catatan_sumber' => ['required', 'string', 'min:15', 'max:500'],
        ];
    }

    public function edit(int $id): void
    {
        $trail = Trail::findOrFail($id);
        $this->authorize('update', $trail);

        $this->editingId = $trail->id;
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
        $this->description = $trail->description;
        $this->catatan_sumber = null;
    }

    public function batal(): void
    {
        $this->reset();
    }

    public function simpan(AuditLogService $audit): void
    {
        $trail = Trail::findOrFail($this->editingId);
        $this->authorize('update', $trail);

        $this->validate();

        $kredensial = auth()->user()->usableTrailCredentialFor($trail->mountain_id);

        // Penjagaan kedua. Policy sudah memeriksanya, tetapi kredensial dapat kedaluwarsa
        // atau dicabut di antara membuka formulir dan menekan simpan.
        if ($kredensial === null) {
            session()->flash('status', 'Kredensial Anda tidak lagi berlaku untuk kawasan ini.');

            return;
        }

        $sebelum = $trail->only([
            'distance_km', 'elevation_gain_m', 'elevation_loss_m', 'estimated_duration_minutes',
            'technical_demand', 'navigation_complexity', 'water_availability', 'terrain_character',
            'camping_available', 'starting_point', 'description',
        ]);

        $trail->update([
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
            'description' => $this->description,
            'data_source_id' => $this->sumberUntuk($kredensial)->id,
        ]);

        // Siapa menyumbang atas dasar sertifikat mana adalah fakta yang harus tersimpan,
        // bukan disimpulkan belakangan (§60).
        $trail->forceFill([
            'contributed_by' => auth()->id(),
            'contributed_credential_id' => $kredensial->id,
            'contributed_at' => now(),
        ])->save();

        $audit->record(auth()->user(), 'trail.contributed', $trail, $sebelum, [
            'credential_id' => $kredensial->id,
            'level' => $kredensial->level->value,
            'catatan_sumber' => $this->catatan_sumber,
        ]);

        $this->reset();

        session()->flash('status', 'Data tersimpan. Jalur ini belum tayang; admin yang memutuskan '
            .'kelayakannya setelah seluruh syarat publikasi terpenuhi.');
    }

    /**
     * Sumber data atas nama ahli yang menyumbang, bukan atas nama aplikasi.
     */
    private function sumberUntuk(ExpertCredential $kredensial): DataSource
    {
        return DataSource::updateOrCreate(
            ['source_name' => 'Kontribusi ahli: '.$kredensial->user->name],
            [
                'source_type' => SourceType::ACCREDITED_EXPERT->value,
                'source_owner' => $kredensial->issuingAuthority->displayName(),
                'retrieved_at' => now(),
                'verified_at' => $kredensial->verified_at,
                'freshness_policy' => 'Data lapangan berubah mengikuti musim dan kondisi jalur. '
                    .'Periksa ulang bersama kontributor secara berkala.',
                'notes' => $kredensial->level->label()
                    .($kredensial->certificate_number ? ', sertifikat '.$kredensial->certificate_number : '')
                    .'. '.$this->catatan_sumber,
            ]
        );
    }

    /**
     * @return Collection<int, ExpertCredential>
     */
    private function kredensialAktif()
    {
        return auth()->user()->expertCredentials()
            ->usable()
            ->with('mountains')
            ->get()
            ->filter(fn (ExpertCredential $k) => $k->mayContributeTrailData())
            ->values();
    }

    public function render()
    {
        $kredensial = $this->kredensialAktif();
        $gunungId = $kredensial->flatMap(fn (ExpertCredential $k) => $k->mountains->pluck('id'))->unique();

        return view('livewire.contribute.trail-contribution', [
            'kredensial' => $kredensial,
            'trails' => Trail::query()
                ->active()
                ->whereIn('mountain_id', $gunungId)
                ->with('mountain')
                // Yang datanya belum lengkap didahulukan: itu yang paling butuh diisi.
                ->orderBy('is_published')
                ->orderBy('name')
                ->get(),
            'technicalLevels' => TechnicalDemand::cases(),
            'navigationLevels' => NavigationComplexity::cases(),
            'waterLevels' => WaterAvailability::cases(),
            'terrainOptions' => TerrainCharacter::cases(),
        ]);
    }
}
