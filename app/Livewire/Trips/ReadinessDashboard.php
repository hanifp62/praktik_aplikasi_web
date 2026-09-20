<?php

namespace App\Livewire\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Services\AnalyticsRecorder;
use App\Services\ReadinessService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-10 readiness: route fit + preparation + current conditions (PRD §38-39).
 * This is decision support, not a safety clearance.
 *
 * Membuka halaman ini hanya menghitung, tidak menyimpan. Baris ReadinessCheck baru
 * ditulis ketika pengguna meminta perhitungan ulang atau mengonfirmasi pre-departure
 * check, keduanya peristiwa nyata yang layak masuk riwayat, tidak seperti sekadar
 * memuat halaman.
 */
#[Layout('layouts.app')]
#[Title('Kesiapan Pendakian')]
class ReadinessDashboard extends Component
{
    public TripPlan $trip;

    public bool $preDepartureConfirmed = false;

    /**
     * Penolakan dipisahkan dari flash 'status'.
     *
     * Tampilan merender flash sebagai alert hijau, sehingga "belum dapat dikonfirmasi"
     * muncul dalam kotak yang terbaca seperti berhasil. Flash juga alat untuk redirect,
     * sementara di sini tidak ada redirect.
     */
    public ?string $penolakan = null;

    public function mount(TripPlan $trip, ReadinessService $readiness): void
    {
        $this->authorize('view', $trip);

        $this->trip = $trip->load('trail.mountain');
        $this->preDepartureConfirmed = (bool) $readiness->latestCheck($this->trip)?->pre_departure_confirmed;
    }

    public function recompute(ReadinessService $readiness): void
    {
        $this->authorize('update', $this->trip);

        $readiness->record($this->trip);

        session()->flash('status', 'Kesiapan dihitung ulang.');
    }

    public function confirmPreDeparture(ReadinessService $readiness, AnalyticsRecorder $analytics): void
    {
        $this->authorize('update', $this->trip);

        $this->penolakan = null;

        $assessment = $readiness->compute($this->trip);

        // Alasannya sudah dihitung, jadi sebutkan. Penolakan yang tidak memberi tahu apa
        // yang menahannya hanya memindahkan kebuntuan dari sistem ke pendaki, dan ini
        // terjadi persis pada saat ia hendak memastikan dirinya layak berangkat.
        if ($assessment->state === ReadinessState::NOT_RECOMMENDED) {
            $alasan = $assessment->explanation['reasons'] ?? [];

            $this->penolakan = $alasan === []
                ? 'Pre-departure check belum dapat dikonfirmasi. Periksa rincian kesiapan di bawah.'
                : 'Pre-departure check belum dapat dikonfirmasi. '.implode(' ', array_unique($alasan));

            return;
        }

        if (! $this->trip->status->canTransitionTo(TripStatus::READY_FOR_DEPARTURE)) {
            // Menyebut status yang sekarang lebih berguna daripada mengatakan tindakannya
            // tidak mungkin: pendaki tidak melihat nilai status itu di layar mana pun.
            $this->penolakan = sprintf(
                'Trip ini berstatus %s, sehingga tidak dapat ditandai siap berangkat.',
                $this->trip->status->label()
            );

            return;
        }

        $check = $readiness->record($this->trip, $assessment);
        $check->update(['pre_departure_confirmed' => true]);

        $this->trip->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);
        $this->preDepartureConfirmed = true;

        $analytics->record(AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED, auth()->user(), [
            'trip_plan_id' => $this->trip->id,
        ]);

        session()->flash('status', 'Pre-departure check tercatat.');
    }

    public function render(ReadinessService $readiness)
    {
        $assessment = $readiness->compute($this->trip);

        // Tidak disimpan, hanya untuk ditampilkan; data dari render() tidak ikut
        // diserialisasi Livewire sehingga model tanpa id tidak perlu dihidrasi.
        $check = new ReadinessCheck($assessment->toAttributes());

        return view('livewire.trips.readiness-dashboard', [
            'check' => $check,
            'preDepartureConfirmedAt' => $readiness->latestCheck($this->trip)?->updated_at,
        ]);
    }
}
