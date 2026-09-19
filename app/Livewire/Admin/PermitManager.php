<?php

namespace App\Livewire\Admin;

use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Services\AuditLogService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pengelolaan aturan perizinan pendakian (PRD §36 Logistik, §60, §61).
 *
 * Aturan izin menentukan apakah sebuah pendakian dapat terjadi sama sekali, dan ia
 * berubah, kuota disesuaikan, jendela pemesanan digeser, kewajiban pemandu
 * ditambahkan. Karena itu tiap baris membawa sumber dan waktu verifikasi, dan setiap
 * perubahannya masuk jejak audit.
 *
 * Yang dikelola di sini adalah catatan tentang aturan pihak lain. Sistem tidak
 * memesan izin dan tidak melacak kuota.
 */
#[Layout('layouts.app')]
#[Title('Kelola Perizinan')]
class PermitManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    /** trail | mountain */
    public string $scope = 'mountain';

    public ?int $trail_id = null;

    public ?int $mountain_id = null;

    public string $authority = '';

    public ?string $booking_url = null;

    public ?int $daily_quota = null;

    public ?int $booking_opens_days_before = null;

    public ?int $booking_closes_days_before = null;

    public bool $guide_required = false;

    public ?int $max_duration_days = null;

    public ?string $notes = null;

    public ?string $source = null;

    public ?string $source_url = null;

    public function mount(): void
    {
        $this->authorize('viewAny', PermitRequirement::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['trail', 'mountain'])],

            // Sasaran wajib diisi sesuai scope-nya; aturan tanpa sasaran tidak dapat
            // dicocokkan ke jalur mana pun dan hanya akan menjadi baris mati.
            'trail_id' => [Rule::requiredIf($this->scope === 'trail'), 'nullable', 'exists:trails,id'],
            'mountain_id' => [Rule::requiredIf($this->scope === 'mountain'), 'nullable', 'exists:mountains,id'],

            'authority' => ['required', 'string', 'max:160'],
            'booking_url' => ['nullable', 'url', 'max:500'],
            'daily_quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'booking_opens_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],

            // Perbandingan hanya berlaku bila keduanya diisi; banyak pengelola hanya
            // mengumumkan batas tutupnya saja.
            'booking_closes_days_before' => array_merge(
                ['nullable', 'integer', 'min:0', 'max:365'],
                $this->booking_opens_days_before !== null ? ['lte:booking_opens_days_before'] : []
            ),

            'max_duration_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'guide_required' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'trail_id.required' => 'Pilih jalur yang aturannya dicatat.',
            'mountain_id.required' => 'Pilih gunung yang aturannya dicatat.',
            'booking_closes_days_before.lte' => 'Penutupan pemesanan tidak boleh lebih awal daripada pembukaannya.',
        ];
    }

    public function edit(int $id): void
    {
        $this->authorize('update', PermitRequirement::class);

        $rule = PermitRequirement::findOrFail($id);

        $this->editingId = $rule->id;
        $this->scope = $rule->trail_id !== null ? 'trail' : 'mountain';
        $this->trail_id = $rule->trail_id;
        $this->mountain_id = $rule->mountain_id;
        $this->authority = $rule->authority;
        $this->booking_url = $rule->booking_url;
        $this->daily_quota = $rule->daily_quota;
        $this->booking_opens_days_before = $rule->booking_opens_days_before;
        $this->booking_closes_days_before = $rule->booking_closes_days_before;
        $this->guide_required = $rule->guide_required;
        $this->max_duration_days = $rule->max_duration_days;
        $this->notes = $rule->notes;
        $this->source = $rule->source;
        $this->source_url = $rule->source_url;
    }

    public function save(AuditLogService $audit): void
    {
        $this->authorize('update', PermitRequirement::class);
        $this->validate();

        $attributes = [
            // Hanya satu sasaran yang terisi, supaya pencarian aturan tidak ambigu.
            'trail_id' => $this->scope === 'trail' ? $this->trail_id : null,
            'mountain_id' => $this->scope === 'mountain' ? $this->mountain_id : null,
            'authority' => $this->authority,
            'booking_url' => $this->booking_url,
            'daily_quota' => $this->daily_quota,
            'booking_opens_days_before' => $this->booking_opens_days_before,
            'booking_closes_days_before' => $this->booking_closes_days_before,
            'guide_required' => $this->guide_required,
            'max_duration_days' => $this->max_duration_days,
            'notes' => $this->notes,
            'source' => $this->source,
            'source_url' => $this->source_url,

            // Menyimpan berarti kurator baru saja memeriksanya (PRD §60).
            'verified_at' => now(),
        ];

        if ($this->editingId) {
            $rule = PermitRequirement::findOrFail($this->editingId);
            $rule->update($attributes);
            $audit->recordChange(auth()->user(), 'permit_requirement.updated', $rule);
        } else {
            $rule = PermitRequirement::create($attributes);
            $audit->record(auth()->user(), 'permit_requirement.created', $rule, null, $attributes);
        }

        $this->resetForm();
        session()->flash('status', 'Aturan perizinan tersimpan.');
    }

    public function delete(int $id, AuditLogService $audit): void
    {
        $this->authorize('delete', PermitRequirement::class);

        $rule = PermitRequirement::findOrFail($id);
        $audit->recordChange(auth()->user(), 'permit_requirement.deleted', $rule);
        $rule->delete();

        session()->flash('status', 'Aturan perizinan dihapus.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'trail_id', 'mountain_id', 'authority', 'booking_url', 'daily_quota',
            'booking_opens_days_before', 'booking_closes_days_before', 'guide_required',
            'max_duration_days', 'notes', 'source', 'source_url',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.permit-manager', [
            'rules' => PermitRequirement::query()
                ->with('trail.mountain', 'mountain')
                ->orderBy('authority')
                ->paginate(10),
            'trails' => Trail::query()->with('mountain')->orderBy('name')->get(),
            'mountains' => Mountain::query()->orderBy('name')->get(),
        ]);
    }
}
