<?php

namespace App\Livewire\Trails;

use App\Enums\ConsiderationOutcome;
use App\Enums\TechnicalDemand;
use App\Models\Trail;
use App\Services\ConsiderationService;
use App\Services\TrailFitService;
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

    /**
     * Penolakan pada jalur keenam dinyatakan, bukan didiamkan. Tombol yang ditekan lalu
     * tidak terjadi apa-apa membuat orang menekannya lagi.
     *
     * toggle() mengembalikan enum, bukan bool: DITOLAK dibaca langsung dari situ, tidak
     * disimpulkan dari selisih hitungan sebelum dan sesudah. Menyimpulkan dari hitungan
     * salah menandai penghapusan sebagai penolakan pada kasus tertentu, dan itu persis
     * kegagalan diam yang membuat enum ini ada.
     */
    public function timbang(int $trailId, ConsiderationService $consideration): void
    {
        $trail = Trail::active()->findOrFail($trailId);

        $hasil = $consideration->toggle(auth()->user(), $trail);

        if ($hasil === ConsiderationOutcome::DITOLAK) {
            session()->flash('timbangan-penuh', 'Timbangan sudah berisi '.ConsiderationService::BATAS.' jalur. Keluarkan satu dulu sebelum menambah.');
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

        // Kecocokan dinilai hanya untuk yang profilnya cukup. Menilai tanpa profil
        // menghasilkan label yang terlihat pasti dan berdasar ketiadaan, dan itu persis
        // yang dilarang §91.
        $ringkasanFit = auth()->user()?->hasCompletedProfile()
            ? app(TrailFitService::class)->forTrails(auth()->user(), $trails->getCollection())
            : [];

        $ditimbang = auth()->check()
            ? app(ConsiderationService::class)->forUser(auth()->user())
            : collect();

        return view('livewire.trails.trail-index', [
            'trails' => $trails,
            'technicalLevels' => TechnicalDemand::cases(),
            'menunggu' => $this->jalurMenunggu(),
            'ringkasanFit' => $ringkasanFit,
            'ditimbang' => $ditimbang,
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
