<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\FactorScore;
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
    /** Urutan tawaran "Jalur lain untuk Anda": Cocok lebih dulu, Kurang cocok tidak ditawarkan. */
    private const URUTAN_TAWARAN = ['COCOK' => 0, 'PERLU_PERSIAPAN' => 1];

    public function __construct(
        private RouteFitService $fit,
        private OfficialStatusService $status,
        private PermitService $permits,
    ) {}

    /**
     * Pemanggil yang sudah memuat status resmi untuk jalur yang sama (indeks dan ruang kerja
     * trip) mengopernya lewat $statuses, supaya status tidak dibaca dua kali dalam satu
     * permintaan (tinjauan Tugas 1 M7).
     *
     * @param  Collection<int, Trail>  $trails
     * @param  array<int, OfficialStatusValue>|null  $statuses  berkunci id jalur
     * @return array<int, TrailFitSummary>
     */
    public function forTrails(User $user, Collection $trails, ?HikingGoal $goal = null, ?array $statuses = null): array
    {
        return array_map(fn (RouteFitResult $hasil) => $this->ringkasan($hasil, $goal), $this->hasil($user, $trails, $goal, $statuses));
    }

    /**
     * Paling banyak $jumlah jalur untuk ditawarkan kepada pendaki ini ("Jalur lain untuk Anda"
     * di Beranda, spec v3 bagian 6.1): hanya yang lolos syarat dasar dan berlabel Cocok atau
     * Perlu persiapan, Cocok lebih dulu, lalu menurut skor internal mesin seperti recommend().
     * Skor itu tidak pernah keluar dari kelas ini (BR-09); yang dikembalikan hanya ringkasan,
     * berkunci id jalur, dalam urutan tawaran.
     *
     * Kartu Cocok dijelaskan why_it_fits. Kartu Perlu persiapan dijelaskan sebab turunnya label
     * dari daftar sebab yang sama dengan alasan() di semua permukaan (daftarSebab(), ruling
     * W5-FB). Status resmi, izin, jendela izin, dan baris elevation gain dari what_to_watch tidak
     * pernah masuk daftar itu: keempatnya tidak pernah menurunkan label, karena
     * RouteFitService::label() tidak membacanya sama sekali. Tidak ada kartu yang kalimatnya sama
     * dengan kartu sebelumnya, KECUALI satu kasus: label dari pita skor semata, tanpa sebab yang
     * dapat disebut atau dengan semua sebabnya sudah dipakai kartu sebelumnya. Saat itu kartunya
     * jatuh ke alasan() dan boleh sama dengan kartu lain.
     *
     * $jumlah nol atau negatif, atau $trails kosong, mengembalikan larik kosong (M4, ruling
     * W5-5b): tanpa penjaga ini, $jumlah negatif terbaca array_slice() sebagai "sejumlah itu
     * dari akhir", bukan "tidak ada".
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, TrailFitSummary>
     */
    public function teratas(User $user, Collection $trails, int $jumlah): array
    {
        if ($jumlah <= 0 || $trails->isEmpty()) {
            return [];
        }

        $ditawarkan = array_filter(
            $this->hasil($user, $trails),
            fn (RouteFitResult $hasil) => $hasil->eligible && isset(self::URUTAN_TAWARAN[$hasil->label?->value]),
        );

        uasort($ditawarkan, fn (RouteFitResult $a, RouteFitResult $b) => [self::URUTAN_TAWARAN[$a->label->value], $b->internalScore]
            <=> [self::URUTAN_TAWARAN[$b->label->value], $a->internalScore]);

        $tawaran = [];
        $dipakai = [];

        foreach (array_slice($ditawarkan, 0, $jumlah, true) as $id => $hasil) {
            $alasan = $hasil->label === RouteFitLabel::COCOK
                // Kalimat yang sudah menjelaskan tawaran sebelumnya tidak diulang di kartu
                // berikutnya (W5-R17): jalur Cocok memakai why_it_fits yang belum dipakai.
                ? collect($hasil->explanation['why_it_fits'] ?? [])->first(fn (string $kalimat) => ! in_array($kalimat, $dipakai, true))
                : $this->alasanPersiapan($hasil, $dipakai);

            $tawaran[$id] = $this->ringkasan($hasil, null, $alasan);
            $dipakai[] = $tawaran[$id]->alasan;
        }

        return $tawaran;
    }

    /**
     * Kalimat kartu Perlu persiapan "Jalur lain untuk Anda": kalimat pertama daftarSebab() yang
     * belum dipakai kartu sebelumnya, jadi kartu pertama selalu sama dengan alasan() jalur itu
     * di Jelajah dan rincian jalur (ruling W5-FB).
     *
     * Null bila daftarnya kosong atau sudah semua dipakai. Daftar kosong hanya terjadi ketika
     * label datang dari pita skor semata tanpa sebab yang dapat disebut; ringkasan() lalu jatuh
     * ke alasan(), dan kartu itu BISA sama dengan kartu sebelumnya.
     *
     * @param  array<int, string>  $dipakai
     */
    private function alasanPersiapan(RouteFitResult $hasil, array $dipakai): ?string
    {
        return collect($this->daftarSebab($hasil))->first(fn (string $k) => ! in_array($k, $dipakai, true));
    }

    /**
     * Sebab jalur yang lolos syarat dasar tidak berlabel Cocok, satu daftar untuk semua
     * permukaan (ruling W5-FB, I2 tinjauan akhir W5), dalam urutan ruling W5-5c butir 1: detail
     * faktor lemah apa pun (kritis atau bukan), lalu kalimat pembatasan segmen yang dibaca
     * label() (RouteFitService::kalimatSegmen(), pembentuk yang sama dengan peringatan()), lalu
     * detail faktor yang datanya belum ada (R-006). Ini bukan urutan pemeriksaan label() sendiri
     * (lemah, data belum ada, lalu segmen), melainkan urutan yang paling berguna dibaca pendaki.
     *
     * @return list<string>
     */
    private function daftarSebab(RouteFitResult $hasil): array
    {
        return [
            ...array_map(fn (FactorScore $f) => $f->detail, $hasil->weakFactors()),
            ...array_map(fn (array $r) => $this->fit->kalimatSegmen($r), $hasil->segmentRestrictions),
            ...array_map(fn (FactorScore $f) => $f->detail, $hasil->unknownFactors()),
        ];
    }

    /**
     * Evaluasi seluruh koleksi dengan data batch, berkunci id jalur. Pembatasan segmen dimuat
     * sekali di sini untuk evaluate(), dan ikut di RouteFitResult untuk daftarSebab(), jadi
     * kalimat alasan tidak menambah kueri (§96).
     *
     * @param  Collection<int, Trail>  $trails
     * @param  array<int, OfficialStatusValue>|null  $statuses  berkunci id jalur
     * @return array<int, RouteFitResult>
     */
    private function hasil(User $user, Collection $trails, ?HikingGoal $goal = null, ?array $statuses = null): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        // Satu query untuk seluruh koleksi; gerbang kelengkapan yang dipanggil per jalur
        // di evaluate() kemudian membaca dari memori, bukan dari basis data (§96).
        Trail::hydratePublishabilityData($trails);

        $statuses ??= $this->status->effectiveStatusesForTrails($trails);
        $restrictions = $this->status->segmentRestrictionsForTrails($trails);
        $weights = $this->fit->weights();

        // RouteFitService::evaluate() mencari izin sendiri per jalur ketika $permit
        // dibiarkan pada sentinel false-nya. requirementsForTrails() sudah menyediakan
        // versi batch untuk kebutuhan yang sama di recommend(); tanpa memuatnya di sini,
        // setiap jalur kembali memanggil dua query perizinan sendiri-sendiri (§96).
        $permits = $this->permits->requirementsForTrails($trails);

        $hasil = [];

        foreach ($trails as $trail) {
            $hasil[$trail->id] = $this->fit->evaluate(
                user: $user,
                goal: $goal,
                trail: $trail,
                weights: $weights,
                status: $statuses[$trail->id] ?? null,
                segmentRestrictions: $restrictions[$trail->id] ?? [],
                permit: $permits[$trail->id] ?? null,
            );
        }

        return $hasil;
    }

    /**
     * Isi "Kenapa?" di kartu Jelajah (spec v3 bagian 6.2): dua kalimat teratas dari penjelasan
     * mesin yang sama dengan halaman rekomendasi, bukan kalimat baru.
     */
    private function ringkasan(RouteFitResult $hasil, ?HikingGoal $goal, ?string $alasan = null): TrailFitSummary
    {
        $alasanAkhir = $alasan ?? $this->alasan($hasil);

        return new TrailFitSummary(
            label: $hasil->label,
            eligible: $hasil->eligible,
            alasan: $alasanAkhir,
            denganRencana: $goal !== null,
            // M3, ruling W5-5b: kalimat yang sudah dipakai sebagai alasan kartu tidak diulang
            // di "Mengapa sesuai". Sebelumnya, jalur Cocok selalu menulis why_it_fits[0] dua
            // kali: sebagai alasan DAN sebagai butir pertama daftar ini. Global di sini karena
            // Jelajah memakai ringkasan yang sama lewat forTrails().
            mengapaSesuai: $hasil->eligible
                ? array_slice(array_filter($hasil->explanation['why_it_fits'] ?? [], fn (string $kalimat) => $kalimat !== $alasanAkhir), 0, 2)
                : [],
            // Ruling W5-FR (N1): "Yang perlu diperhatikan" disaring dengan pola yang sama,
            // juga sebelum dipotong dua. Sebab turunnya label (faktor lemah, segmen, data yang
            // belum ada) selalu ada di what_to_watch, jadi tanpa saringan ini panel "Kenapa?"
            // Jelajah mengulang kalimat kartu dan butir sah berikutnya tidak pernah tampil.
            perluDiperhatikan: array_slice(array_values(array_filter($hasil->explanation['what_to_watch'] ?? [], fn (string $kalimat) => $kalimat !== $alasanAkhir)), 0, 2),
        );
    }

    /**
     * Satu kalimat, dipilih menurut apa yang paling berguna diketahui pembacanya.
     *
     * Ketika sebuah jalur tersingkir, yang ingin ia tahu penyebabnya, bukan kelebihannya.
     * Ketika cocok, yang menolong justru alasan terkuatnya: kalimat pertama why_it_fits dari
     * RecommendationExplanationService (ruling W3-C7, diputuskan W5). Label lain memakai sebab
     * pertama dari daftarSebab(), faktor lemah lebih dulu, karena yang perlu diketahui pendaki
     * adalah apa yang membuatnya belum Cocok; daftar yang sama menjelaskan kartu Beranda, jadi
     * satu jalur tidak pernah mendapat dua kalimat yang berbeda arah (ruling W5-FB). Daftar
     * kosong, yaitu label dari pita skor semata, memakai faktor kuat pertama. Urutan ini yang
     * membuat satu kalimat cukup, dan §89 memang hanya menyediakan satu baris di tingkat daftar.
     * Publik karena rincian jalur memakai kalimat yang sama untuk penilaiannya (W3).
     */
    public function alasan(RouteFitResult $hasil): string
    {
        if (! $hasil->eligible) {
            return $this->alasanTersingkir($hasil->failedRules[0] ?? null);
        }

        if ($hasil->label === RouteFitLabel::COCOK && $hasil->strongFactors() !== []) {
            return $hasil->explanation['why_it_fits'][0];
        }

        $sebab = $this->daftarSebab($hasil);

        if ($sebab !== []) {
            return $sebab[0];
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
