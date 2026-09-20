<?php

namespace App\Services;

use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFit\TrailFitSummary;
use Illuminate\Support\Collection;

/**
 * Kecocokan untuk sekumpulan jalur sekaligus.
 *
 * Diferensiator produk (§8) selama ini hanya dipanggil di halaman hasil rekomendasi,
 * bukan karena mesinnya terbatas melainkan karena tidak ada cara memanggilnya untuk
 * banyak jalur tanpa memanggil status resmi sekali per jalur.
 *
 * OfficialStatusService sudah menyediakan versi batch dari keduanya, dan
 * RouteFitService::evaluate() sudah menerima status dan pembatasan segmen yang dimuat
 * dari luar. Layanan ini hanya menyambungkan keduanya, dan itu sebabnya ia setipis ini.
 */
class TrailFitService
{
    public function __construct(
        private RouteFitService $fit,
        private OfficialStatusService $status,
        private PermitService $permits,
    ) {}

    /**
     * @param  Collection<int, Trail>  $trails
     * @return array<int, TrailFitSummary>
     */
    public function forTrails(User $user, Collection $trails, ?HikingGoal $goal = null): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        $statuses = $this->status->effectiveStatusesForTrails($trails);
        $restrictions = $this->status->segmentRestrictionsForTrails($trails);
        $weights = $this->fit->weights();

        // RouteFitService::evaluate() mencari izin sendiri per jalur ketika $permit
        // dibiarkan pada sentinel false-nya. requirementsForTrails() sudah menyediakan
        // versi batch untuk kebutuhan yang sama di recommend(); tanpa memuatnya di sini,
        // setiap jalur kembali memanggil dua query perizinan sendiri-sendiri (§96).
        $permits = $this->permits->requirementsForTrails($trails);

        $ringkasan = [];

        foreach ($trails as $trail) {
            $hasil = $this->fit->evaluate(
                user: $user,
                goal: $goal,
                trail: $trail,
                weights: $weights,
                status: $statuses[$trail->id] ?? null,
                segmentRestrictions: $restrictions[$trail->id] ?? [],
                permit: $permits[$trail->id] ?? null,
            );

            $ringkasan[$trail->id] = new TrailFitSummary(
                label: $hasil->label,
                eligible: $hasil->eligible,
                alasan: $this->alasan($hasil),
                denganRencana: $goal !== null,
            );
        }

        return $ringkasan;
    }

    /**
     * Satu kalimat, dipilih menurut apa yang paling berguna diketahui pembacanya.
     *
     * Ketika sebuah jalur tersingkir, yang ingin ia tahu penyebabnya, bukan kelebihannya.
     * Ketika cocok, yang menolong justru alasan terkuatnya. Urutan ini yang membuat satu
     * kalimat cukup, dan §89 memang hanya menyediakan satu baris di tingkat daftar.
     */
    private function alasan(RouteFitResult $hasil): string
    {
        if (! $hasil->eligible) {
            return $hasil->failedRules[0] ?? 'Tidak memenuhi syarat dasar untuk ditawarkan.';
        }

        $lemah = $hasil->weakFactors();

        if ($lemah !== []) {
            return $lemah[0]->detail;
        }

        $kuat = $hasil->strongFactors();

        return $kuat !== [] ? $kuat[0]->detail : 'Tidak ada faktor yang menonjol untuk jalur ini.';
    }
}
