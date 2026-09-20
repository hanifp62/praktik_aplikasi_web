<?php

namespace App\Livewire\Trails;

use App\Models\MountainFollow;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Services\CheckpointPaceService;
use App\Services\ConditionAggregatorService;
use App\Services\PermitService;
use App\Services\RouteFitService;
use App\Services\TrailNewsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * FR-07 trail detail: overview, route, checkpoints, preparation context, official status,
 * weather and community conditions, each with its own source (PRD §34, §60).
 */
#[Layout('layouts.app')]
class TrailDetail extends Component
{
    public Trail $trail;

    public function mount(Trail $trail): void
    {
        abort_if($trail->archived_at !== null, 404);

        $this->trail = $trail->load('mountain', 'segments', 'checkpoints', 'dataSource');
    }

    /**
     * Berterima kasih pada laporan yang menolong, atau menariknya kembali.
     *
     * Menggantikan papan peringkat kontribusi yang dibatalkan setelah risetnya. Yang
     * dihargai kegunaannya, dan yang menilainya pendaki lain yang membacanya sebelum
     * berangkat.
     *
     * Ketiga penjagaan di bawah menjaga satu hal yang sama: angkanya tidak boleh dapat
     * dinaikkan sendirian. Angka yang dapat dinaikkan sendirian tidak mengukur apa-apa.
     */
    public function berterimaKasih(int $reportId, bool $ya = true): void
    {
        $laporan = $this->trail->conditionReports()
            ->visibleToPublic()
            ->whereKey($reportId)
            ->first();

        // Laporan yang belum lolos moderasi belum terlihat siapa pun, jadi tidak ada
        // yang dapat menyatakan laporan itu menolongnya. Jalur lain juga tidak dapat
        // disentuh dari halaman ini.
        if ($laporan === null || $laporan->user_id === auth()->id()) {
            return;
        }

        $kunci = ['trail_condition_report_id' => $laporan->id, 'user_id' => auth()->id()];

        $ya
            ? ReportThank::firstOrCreate($kunci)
            : ReportThank::where($kunci)->delete();
    }

    /**
     * Laporan yang sudah diberi terima kasih oleh pembaca ini.
     *
     * Satu query untuk seluruh daftar, bukan satu per laporan: tanpa ini jumlah query
     * tumbuh seiring jumlah laporan yang ditampilkan.
     *
     * @param  array<int, int>  $reportIds
     * @return array<int, int>
     */
    private function sudahBerterimaKasih(array $reportIds): array
    {
        if ($reportIds === [] || auth()->guest()) {
            return [];
        }

        return ReportThank::query()
            ->where('user_id', auth()->id())
            ->whereIn('trail_condition_report_id', $reportIds)
            ->pluck('trail_condition_report_id')
            ->all();
    }

    /**
     * Mengikuti gunung jalur ini, atau berhenti mengikutinya.
     *
     * Gunungnya yang diikuti, bukan jalurnya: penutupan hampir selalu diumumkan untuk
     * kawasan, dan pendaki yang mengikuti satu jalur Merbabu tetap perlu tahu ketika
     * seluruh Merbabu ditutup.
     */
    public function ikutiGunung(bool $ya = true): void
    {
        $kunci = ['user_id' => auth()->id(), 'mountain_id' => $this->trail->mountain_id];

        $ya
            ? MountainFollow::firstOrCreate($kunci)
            : MountainFollow::where($kunci)->delete();
    }

    public function createTrip(): void
    {
        $this->redirectRoute('trips.create', ['trail' => $this->trail->id], navigate: true);
    }

    public function render(ConditionAggregatorService $conditions, RouteFitService $routeFit)
    {
        // Jalur yang belum terbit tidak punya cukup data untuk dinilai maupun
        // direncanakan. Halamannya tetap dapat dibuka, tetapi yang ditampilkan adalah
        // keadaan menunggu, bukan rincian setengah jadi yang terbaca seperti rincian utuh.
        if (! $this->trail->is_published) {
            return view('livewire.trails.trail-awaiting', [
                'menunggu' => $this->trail->awaitingData(),
                'badan' => $this->trail->mountain->responsibleAuthority(),
            ])->title($this->trail->name.' - '.$this->trail->mountain->name);
        }

        $user = auth()->user();
        $fit = $user?->hasCompletedProfile()
            ? $routeFit->evaluate($user, $user->hikingGoals()->latest()->first(), $this->trail)
            : null;

        $kondisi = $conditions->forTrail($this->trail);

        return view('livewire.trails.trail-detail', [
            'conditions' => $kondisi,
            'fit' => $fit,
            'geometry' => $this->trail->readGeoJson('geometry'),
            'permit' => app(PermitService::class)->requirementFor($this->trail),
            // Waktu tempuh antarpos dari rekaman pendaki. Hasilnya di-cache mengikuti
            // TTL publik, jadi halaman ini tidak menghitung ulang tiap kali dibuka.
            'tempoPos' => app(CheckpointPaceService::class)->forTrail($this->trail),
            'mengikutiGunung' => app(TrailNewsService::class)->follows($user, $this->trail->mountain),
            'terimaKasihSaya' => $this->sudahBerterimaKasih(
                collect($kondisi['community_context']['reports'] ?? [])->pluck('id')->all()
            ),
        ])->title($this->trail->name.' - '.$this->trail->mountain->name);
    }
}
