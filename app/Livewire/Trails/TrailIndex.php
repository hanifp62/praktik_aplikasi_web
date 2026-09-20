<?php

namespace App\Livewire\Trails;

use App\Enums\TechnicalDemand;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manual browse/filter fallback required when recommendation fails (PRD §94).
 */
#[Layout('layouts.app')]
#[Title('Jelajahi Jalur')]
class TrailIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $technical = '';

    #[Url]
    public string $region = '';

    public function updating(string $field): void
    {
        if (in_array($field, ['search', 'technical', 'region'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $trails = Trail::query()
            ->published()
            ->with('mountain')
            // Wajib dikurung: tanpa grup, OR-nya menggantung di luar published() dan
            // jalur draft ikut lolos begitu nama gunungnya cocok.
            ->when($this->search, fn ($query, $search) => $query->where(
                fn ($group) => $group->where('name', 'like', "%{$search}%")
                    ->orWhereHas('mountain', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ))
            ->when($this->technical, fn ($query, $technical) => $query->where('technical_demand', $technical))
            ->when($this->region, fn ($query, $region) => $query->whereHas(
                'mountain',
                fn ($q) => $q->where('region', $region)->orWhere('province', $region)
            ))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.trails.trail-index', [
            'trails' => $trails,
            'technicalLevels' => TechnicalDemand::cases(),
            'menunggu' => $this->jalurMenunggu(),
        ]);
    }

    /**
     * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang.
     *
     * Ditampilkan terpisah, tidak pernah dicampur ke hasil. Pendaki yang mencari "Lawu"
     * tanpa ini hanya melihat layar kosong, seolah gunungnya tidak ada, padahal yang
     * belum ada adalah keterangannya.
     *
     * Dibatasi jumlahnya karena ini bukan daftar utama, melainkan petunjuk bahwa jalurnya
     * dikenal dan sedang menunggu.
     *
     * @return Collection<int, Trail>
     */
    private function jalurMenunggu()
    {
        return Trail::query()
            ->active()
            ->where('is_published', false)
            ->with('mountain')
            ->when($this->search, fn ($query, $search) => $query->where(
                fn ($group) => $group->where('name', 'like', "%{$search}%")
                    ->orWhereHas('mountain', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ))
            ->when($this->region, fn ($query, $region) => $query->whereHas(
                'mountain',
                fn ($q) => $q->where('region', $region)->orWhere('province', $region)
            ))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }
}
