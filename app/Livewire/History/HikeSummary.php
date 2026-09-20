<?php

namespace App\Livewire\History;

use App\Models\Checkpoint;
use App\Models\TripPlan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Hasil satu pendakian.
 *
 * Peak-end rule mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya
 * diingat, dan puncak emosional produk ini selama ini berakhir di kotak isian catatan
 * pribadi: pendaki menekan "Tandai selesai" lalu tidak pernah melihat apa pun lagi.
 *
 * Seluruh isinya berasal dari data yang sudah tersimpan. Yang digambar adalah garis
 * jalur resmi, bukan jejak pendaki, karena jejak pendaki memang belum pernah direkam
 * dan aplikasi ini tidak menggambar yang tidak diketahuinya.
 */
#[Layout('layouts.app')]
class HikeSummary extends Component
{
    public TripPlan $trip;

    public function mount(TripPlan $trip): void
    {
        $this->authorize('view', $trip);

        // Trip yang belum diselesaikan belum punya hasil untuk diringkas, dan ringkasan
        // kosong akan terbaca seolah pendakiannya sudah usai.
        abort_unless($trip->history()->exists(), 404);

        $this->trip = $trip->load('trail.mountain', 'trail.checkpoints', 'hikingSession', 'history');
    }

    /**
     * Lama pendakian menurut sesi, bukan menurut tanggal rencana.
     *
     * Null ketika hike mode tidak pernah dibuka, dan itu keadaan yang umum, bukan
     * pinggiran. Nol jam akan menjadi pernyataan yang salah.
     */
    public function durasi(): ?string
    {
        $sesi = $this->trip->hikingSession;

        if ($sesi?->started_at === null || $sesi?->ended_at === null) {
            return null;
        }

        $menit = (int) round($sesi->started_at->diffInMinutes($sesi->ended_at));

        return $menit < 60
            ? $menit.' menit'
            : intdiv($menit, 60).' jam '.($menit % 60).' menit';
    }

    /**
     * Pos terjauh yang dicapai, dinamai alih-alih disebut sebagai angka urutan.
     */
    public function posTerjauh(): ?Checkpoint
    {
        $urutan = $this->trip->hikingSession?->reached_sequence;

        if ($urutan === null) {
            return null;
        }

        return $this->trip->trail->checkpoints->firstWhere('sequence', $urutan);
    }

    public function render()
    {
        return view('livewire.history.hike-summary')
            ->title('Hasil pendakian '.$this->trip->trail->name);
    }
}
