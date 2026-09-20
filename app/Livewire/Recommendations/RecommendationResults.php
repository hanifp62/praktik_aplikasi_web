<?php

namespace App\Livewire\Recommendations;

use App\Enums\AnalyticsEvent;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Services\AnalyticsRecorder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-05 recommendation result page with per-route explainability (PRD §30, §32).
 */
#[Layout('layouts.app')]
#[Title('Rekomendasi Jalur')]
class RecommendationResults extends Component
{
    public RecommendationRun $run;

    public ?int $expandedResultId = null;

    /** @var array<int, int> */
    public array $comparison = [];

    /**
     * Berapa jalur teratas yang dirender. Sisanya ditambahkan sepuluh demi sepuluh
     * atas permintaan, tidak pernah dibuang.
     */
    public int $ditampilkan = self::SEKALI_TAMPIL;

    private const SEKALI_TAMPIL = 10;

    public function tampilkanLagi(): void
    {
        $this->ditampilkan += self::SEKALI_TAMPIL;
    }

    public function mount(RecommendationRun $run): void
    {
        abort_unless($run->user_id === auth()->id(), 403);

        $this->run = $run->load('results.trail.mountain', 'hikingGoal');
    }

    public function toggleExplanation(int $resultId): void
    {
        $this->expandedResultId = $this->expandedResultId === $resultId ? null : $resultId;
    }

    public function toggleComparison(int $trailId): void
    {
        if (in_array($trailId, $this->comparison, true)) {
            $this->comparison = array_values(array_diff($this->comparison, [$trailId]));

            return;
        }

        if (count($this->comparison) < 3) {
            $this->comparison[] = $trailId;
        }
    }

    public function compare(): void
    {
        if (count($this->comparison) < 2) {
            return;
        }

        $this->redirectRoute('trails.compare', ['trails' => implode(',', $this->comparison)], navigate: true);
    }

    public function selectTrail(int $trailId, AnalyticsRecorder $analytics): void
    {
        // Tombolnya sudah hilang dari tampilan, tetapi halaman yang dimuat sebelum jalur
        // itu diarsipkan masih dapat memanggil aksi ini. Pembuatan trip akan menolaknya
        // juga, hanya saja pendaki baru mengetahuinya setelah berpindah halaman dan
        // membaca galat pada form yang tidak pernah ia isi.
        if (! Trail::published()->whereKey($trailId)->exists()) {
            $this->addError('trail', 'Jalur ini sudah ditarik dari katalog dan tidak dapat dipilih lagi.');

            return;
        }

        $analytics->record(AnalyticsEvent::ROUTE_SELECTED, auth()->user(), ['trail_id' => $trailId]);

        $this->redirectRoute('trips.create', ['trail' => $trailId, 'goal' => $this->run->hiking_goal_id], navigate: true);
    }

    public function render()
    {
        $results = $this->run->results;
        $eligible = $results->where('eligible', true);

        return view('livewire.recommendations.recommendation-results', [
            'eligible' => $eligible->take($this->ditampilkan),
            'sisaEligible' => max(0, $eligible->count() - $this->ditampilkan),
            'excluded' => $results->where('eligible', false),
            'penyebabKosong' => $this->penyebabKosong($results),
        ]);
    }

    /**
     * Layar kosong punya tiga sebab yang menuntut tiga saran berbeda, dan saran yang
     * salah lebih buruk daripada tidak ada saran.
     *
     * Penyaring wilayah bekerja di tingkat query, jadi jalur yang tersaring tidak
     * pernah menjadi kandidat maupun jalur tersingkir. Ketika itu terjadi, menyuruh
     * pendaki melonggarkan durasi dan batas elevation gain menyuruhnya mengubah dua
     * hal yang tidak pernah diuji terhadap satu jalur pun.
     *
     * Hasil kosong sama sekali hanya mungkin karena dua hal: tidak ada jalur terbit,
     * atau wilayahnya menyaring semuanya. Hasil ada tetapi tak satu pun lolos berarti
     * batasan rencananya yang mengikat, dan di situ saran lama memang benar.
     */
    private function penyebabKosong(Collection $results): ?string
    {
        if ($results->isNotEmpty()) {
            return null;
        }

        if (! Trail::published()->exists()) {
            return 'katalog';
        }

        return $this->run->hikingGoal?->region ? 'wilayah' : 'batasan';
    }
}
