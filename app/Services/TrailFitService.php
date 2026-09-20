<?php

namespace App\Services;

use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFit\TrailFitSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Kecocokan untuk sekumpulan jalur sekaligus.
 *
 * Diferensiator produk (§8) selama ini hanya dipanggil di halaman hasil rekomendasi,
 * bukan karena mesinnya terbatas melainkan karena tidak ada cara memanggilnya untuk
 * banyak jalur tanpa memanggil status resmi sekali per jalur.
 *
 * OfficialStatusService dan PermitService masing-masing sudah menyediakan versi batch
 * dari data yang tadinya diambil ulang per jalur, dan RouteFitService::evaluate() sudah
 * menerima ketiganya (status, pembatasan segmen, izin) dimuat dari luar. Ketiganya
 * dipanggil di sini, bukan hanya dua, karena mengabaikan salah satunya membuat anggaran
 * query kembali tumbuh linear terhadap jumlah jalur (§96) -- PermitService khususnya
 * yang tadinya luput sampai diketahui lewat pengukuran, bukan lewat dugaan.
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
            return $this->alasanTersingkir($hasil->failedRules[0] ?? null);
        }

        $lemah = $hasil->weakFactors();

        if ($lemah !== []) {
            return $lemah[0]->detail;
        }

        $kuat = $hasil->strongFactors();

        return $kuat !== [] ? $kuat[0]->detail : 'Tidak ada faktor yang menonjol untuk jalur ini.';
    }

    /**
     * failedRules berisi kunci mesin seperti official_status_closed, bukan kalimat --
     * menampilkannya mentah adalah persis pelanggaran §90 yang metode ini ada untuk
     * mencegah. Lang::has() dicek secara eksplisit, bukan diserahkan ke __(), karena
     * __() pada kunci yang tidak diterjemahkan mengembalikan kuncinya sendiri: itu
     * cacat yang sama, hanya tertunda sampai ada aturan baru yang belum diberi kalimat.
     */
    private function alasanTersingkir(?string $rule): string
    {
        if ($rule === null) {
            return 'Tidak memenuhi syarat dasar untuk ditawarkan.';
        }

        return Lang::has('recommendation.'.$rule)
            ? __('recommendation.'.$rule)
            : 'Tidak memenuhi syarat dasar untuk ditawarkan.';
    }
}
