<?php

namespace App\Livewire\Admin;

use App\Enums\AuthorityType;
use App\Models\Authority;
use App\Models\Mountain;
use App\Services\AuditLogService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Mendaftarkan badan resmi dan kawasan yang berada di bawahnya (PRD §43).
 *
 * Badan resmi sebelumnya hanya dapat masuk lewat seeder. Padahal daftarnya berubah:
 * balai berganti nama, pengelola basecamp bertambah, dan setiap gunung baru butuh
 * disebutkan siapa yang berwenang atasnya sebelum halaman "menunggu masukan" dapat
 * menyebut nama.
 */
#[Layout('layouts.app')]
#[Title('Kelola Badan Resmi')]
class AuthorityManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $name = '';

    public string $type = 'NATIONAL_PARK';

    public ?string $abbreviation = null;

    public ?string $website = null;

    public ?string $contact = null;

    public ?string $jurisdiction = null;

    public ?string $notes = null;

    /** @var array<int, int> */
    public array $mountain_ids = [];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', new Enum(AuthorityType::class)],
            'abbreviation' => ['nullable', 'string', 'max:24'],
            'website' => ['nullable', 'url', 'max:200'],
            'contact' => ['nullable', 'string', 'max:160'],
            'jurisdiction' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'mountain_ids' => ['array'],
            'mountain_ids.*' => [Rule::exists('mountains', 'id')],
        ];
    }

    public function create(): void
    {
        $this->authorize('manageAuthorities', Authority::class);

        $this->reset(['editingId', 'name', 'abbreviation', 'website', 'contact', 'jurisdiction', 'notes', 'mountain_ids']);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('manageAuthorities', Authority::class);

        $badan = Authority::with('mountains')->findOrFail($id);

        $this->editingId = $badan->id;
        $this->name = $badan->name;
        $this->type = $badan->type->value;
        $this->abbreviation = $badan->abbreviation;
        $this->website = $badan->website;
        $this->contact = $badan->contact;
        $this->jurisdiction = $badan->jurisdiction;
        $this->notes = $badan->notes;
        $this->mountain_ids = $badan->mountains->pluck('id')->all();
        $this->showForm = true;
    }

    public function save(AuditLogService $audit): void
    {
        $this->authorize('manageAuthorities', Authority::class);

        $this->validate();

        $atribut = [
            'name' => $this->name,
            'type' => $this->type,
            'abbreviation' => $this->abbreviation,
            'website' => $this->website,
            'contact' => $this->contact,
            'jurisdiction' => $this->jurisdiction,
            'notes' => $this->notes,
        ];

        if ($this->editingId) {
            $badan = Authority::findOrFail($this->editingId);
            $badan->update($atribut);
            $audit->recordChange(auth()->user(), 'authority.updated', $badan);
        } else {
            $badan = Authority::create($atribut + [
                'slug' => Str::slug($this->name),
                'verified_at' => now(),
            ]);
            $audit->record(auth()->user(), 'authority.created', $badan, null, $atribut);
        }

        $badan->mountains()->sync($this->mountain_ids);

        $this->showForm = false;
        session()->flash('status', 'Badan resmi tersimpan.');
    }

    public function render()
    {
        $this->authorize('manageAuthorities', Authority::class);

        return view('livewire.admin.authority-manager', [
            'badan' => Authority::query()->withCount('mountains')->orderBy('name')->paginate(10),
            'types' => AuthorityType::cases(),
            'mountains' => Mountain::active()->orderBy('name')->get(),
        ]);
    }
}
