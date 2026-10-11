<?php

namespace App\Services;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\ReadinessState;
use App\Enums\RouteFitLabel;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Services\Readiness\ReadinessAssessment;
use App\Services\RouteFit\RouteFitResult;
use Carbon\CarbonInterface;

/**
 * Readiness combines route fit, preparation state and current conditions (PRD §38-39).
 * It is decision support, never a medical or safety clearance (PRD §40).
 */
class ReadinessService
{
    public function __construct(
        private readonly RouteFitService $routeFit,
        private readonly PreparationService $preparation,
        private readonly OfficialStatusService $officialStatus,
        private readonly ConditionAggregatorService $conditions,
    ) {}

    /**
     * Menghitung readiness tanpa menyentuh basis data untuk menulis.
     *
     * Membuka halaman readiness bukan sebuah peristiwa yang layak dicatat; yang layak
     * dicatat adalah ketika pengguna meminta perhitungan ulang atau mengonfirmasi
     * pre-departure check.
     */
    public function compute(TripPlan $trip): ReadinessAssessment
    {
        $trip->loadMissing(['trail.mountain', 'preparationItems', 'user.profile', 'user.experience', 'user.preference', 'hikingGoal']);

        // Kecocokan untuk rencana ini (spec v3 bagian 6.11): jenis dan tanggal trip di atas
        // salinan tujuannya (TripPlan::tujuanPenilaian(), ruling W4-FA), sama dengan ruang kerja
        // dan halaman penilaian kecocokan. Tanpa itu "tektok" untuk jalur bermalam tidak pernah
        // tertahan syarat dasar.
        $fit = $this->routeFit->evaluate($trip->user, $trip->tujuanPenilaian(), $trip->trail);
        $preparationState = $this->preparation->state($trip);
        $status = $this->officialStatus->effectiveStatusForTrail($trip->trail);
        $conditions = $this->conditions->forTrail($trip->trail);

        // I4 (putaran perbaikan 1): hardConstraintFailures() tidak memakai data pengguna, jadi
        // syarat dasar (NOT_RECOMMENDED) tetap berlaku tanpa profil. Tetapi labelnya berasal
        // dari skor pengguna (CompatibilityScorer), jadi tanpa profil label itu bukan penilaian
        // yang pernah dilihat pendaki (kartu kecocokan menyebutnya "Belum dinilai", R-006); label
        // yang sama tidak boleh diam-diam menurunkan keadaan lewat cabang KURANG_COCOK di bawah.
        $profilLengkap = $trip->user->hasCompletedProfile();

        // Yang dinilai di sini adalah sebuah rencana, jadi status pada tanggal rencananya
        // sama menentukannya dengan status hari ini. Penutupan besar di Indonesia bersifat
        // tahunan dan diumumkan jauh hari, sehingga rencana untuk Januari dapat jatuh ke
        // penutupan yang sudah tercatat sekarang.
        $statusTanggalRencana = $trip->planned_date
            ? $this->officialStatus->effectiveStatusForTrailOn($trip->trail, $trip->planned_date)
            : $status;

        $state = $this->determineState($fit, $status, $preparationState, $conditions, $statusTanggalRencana, $profilLengkap);

        return new ReadinessAssessment(
            state: $state,
            routeFitSnapshot: [
                'label' => $profilLengkap ? $fit->label?->value : null,
                'eligible' => $fit->eligible,
                'failed_rules' => $fit->failedRules,
                'engine_version' => $fit->engineVersion,
            ],
            preparationState: $preparationState,
            officialStatusSnapshot: $conditions['official_status'],
            conditionSnapshot: [
                'weather' => $conditions['weather_context'],
                'community' => $conditions['community_context'],
            ],
            explanation: $this->explain(
                $state,
                $fit,
                $status,
                $preparationState,
                $conditions,
                $profilLengkap,
                $statusTanggalRencana,
                $trip->planned_date,
            ),
        );
    }

    /**
     * Menyimpan hasil perhitungan sebagai satu baris ReadinessCheck. Tinjauan pra-berangkat
     * ditulis sebagai satu baris yang langsung terkonfirmasi, bukan baris yang diperbarui
     * sesudahnya (M5 tinjauan akhir W4).
     */
    public function record(TripPlan $trip, ?ReadinessAssessment $assessment = null, bool $dikonfirmasi = false): ReadinessCheck
    {
        $assessment ??= $this->compute($trip);

        return ReadinessCheck::create(array_merge(
            ['trip_plan_id' => $trip->id],
            $assessment->toAttributes(),
            ['pre_departure_confirmed' => $dikonfirmasi],
        ));
    }

    /**
     * Check tersimpan terakhir untuk trip ini, atau null bila belum pernah dicatat. Dipakai
     * "Sejak cek terakhir": pembandingnya adalah kunjungan terakhir apa pun, bukan hanya yang
     * dikonfirmasi (untuk itu, TripPlan::tinjauanTercatat()).
     */
    public function latestCheck(TripPlan $trip): ?ReadinessCheck
    {
        return ReadinessCheck::query()
            ->where('trip_plan_id', $trip->id)
            ->latest('id')
            ->first();
    }

    /**
     * Hitung lalu simpan. Dipertahankan untuk pemanggil yang memang ingin keduanya.
     */
    public function evaluate(TripPlan $trip): ReadinessCheck
    {
        return $this->record($trip);
    }

    /**
     * @param  array<string, mixed>  $preparationState
     * @param  array<string, mixed>  $conditions
     */
    private function determineState(
        RouteFitResult $fit,
        OfficialStatusValue $status,
        array $preparationState,
        array $conditions,
        ?OfficialStatusValue $statusTanggalRencana = null,
        bool $profilLengkap = true,
    ): ReadinessState {
        // PRD §39: NOT_RECOMMENDED is reserved for clear product-level conditions such as an
        // official closure or a hard incompatibility - never a judgement about the person.
        //
        // Tutup pada tanggal rencana sama menentukannya dengan tutup hari ini: yang akan
        // dijalani pendaki adalah tanggal itu, bukan hari ini.
        if ($status === OfficialStatusValue::CLOSED
            || $statusTanggalRencana === OfficialStatusValue::CLOSED
            || ! $fit->eligible) {
            return ReadinessState::NOT_RECOMMENDED;
        }

        $criticalOutstanding = $preparationState['critical_outstanding'] ?? [];
        $completion = $preparationState['completion_percent'] ?? 0;

        if ($criticalOutstanding !== [] || $completion < 100) {
            return ReadinessState::NEEDS_PREPARATION;
        }

        // I4: label KURANG_COCOK hanya menurunkan keadaan bila pendaki benar-benar sudah
        // dinilai (profil lengkap). Tanpa profil, label bawaan skornya bukan penilaian yang
        // pernah dilihat pendaki (R-006), jadi tidak boleh diam-diam menjadi alasan keadaan.
        if (($profilLengkap && $fit->label === RouteFitLabel::KURANG_COCOK) || $status === OfficialStatusValue::UNKNOWN) {
            return ReadinessState::NEEDS_PREPARATION;
        }

        // Dimensi ketiga PRD §38. Tidak pernah menaikkan ke NOT_RECOMMENDED, yang tetap
        // khusus penutupan resmi.
        if ($this->hasUnresolvedConditions($conditions)) {
            return ReadinessState::NEEDS_PREPARATION;
        }

        return ReadinessState::READY;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function hasUnresolvedConditions(array $conditions): bool
    {
        // Sengaja hanya route_warnings: ketersediaan data cuaca tidak memblokir, agar
        // BMKG yang tidak dapat dihubungi tidak menahan pengguna dari READY (PRD §94).
        return ($conditions['route_warnings'] ?? []) !== [];
    }

    /**
     * @param  array<string, mixed>  $preparationState
     * @param  array<string, mixed>  $conditions
     * @return array<string, array<int, string>>
     */
    private function explain(
        ReadinessState $state,
        RouteFitResult $fit,
        OfficialStatusValue $status,
        array $preparationState,
        array $conditions,
        bool $profilLengkap,
        ?OfficialStatusValue $statusTanggalRencana = null,
        ?CarbonInterface $tanggalRencana = null,
    ): array {
        $reasons = [];

        // Disebut lebih dulu karena inilah yang paling mungkin mengejutkan: pendaki
        // melihat jalurnya terbuka hari ini, lalu ditolak tanpa tahu sebabnya.
        if ($statusTanggalRencana === OfficialStatusValue::CLOSED && $status !== OfficialStatusValue::CLOSED) {
            $reasons[] = sprintf(
                'Pada tanggal rencana Anda%s jalur ini tercatat TUTUP, meskipun hari ini tidak.',
                $tanggalRencana ? ' ('.$tanggalRencana->translatedFormat('d F Y').')' : ''
            );
        }
        $outstanding = [];
        // n-4 (putaran perbaikan 2): route_warnings dan data_warnings dibawa terpisah, bukan
        // digabung, supaya HalamanKesiapan dapat memilih route_warnings saja untuk
        // "Peringatan kondisi" tanpa menduga-duga dari teks mana asalnya (m10).
        $routeWarnings = $conditions['route_warnings'] ?? [];
        $dataWarnings = $conditions['data_warnings'] ?? [];
        $weather = $conditions['weather_context'] ?? [];

        if ($status === OfficialStatusValue::CLOSED) {
            $reasons[] = 'Status resmi jalur ini tercatat TUTUP.';
        }

        if ($status === OfficialStatusValue::UNKNOWN) {
            $reasons[] = 'Status resmi jalur belum dapat dipastikan, sehingga kondisi terkini belum terverifikasi.';
        }

        if (! $fit->eligible) {
            foreach ($fit->failedRules as $rule) {
                // NIT-2 (putaran perbaikan 3): "official_status_closed" selalu menyertai
                // cabang $status === CLOSED di atas, karena keduanya berasal dari
                // effectiveStatusForTrail() yang sama dalam satu compute(). Menuliskan
                // ulang fakta yang sama dengan kalimat kedua hanya menggandakannya, dan
                // kini tampil dua kali di halaman (daftar alasan dan kalimat tinjauan).
                if ($rule === 'official_status_closed' && $status === OfficialStatusValue::CLOSED) {
                    continue;
                }

                $reasons[] = match ($rule) {
                    'official_status_closed' => 'Jalur tidak tersedia karena status resmi TUTUP.',
                    'trail_not_published' => 'Data jalur ini belum dipublikasikan.',
                    'trail_data_incomplete' => 'Data kritis jalur ini belum lengkap, sehingga '
                        .'kecocokannya belum dapat dinilai.',
                    'trail_archived' => 'Jalur ini ditarik dari katalog aplikasi, sehingga keterangannya '
                        .'tidak lagi diperbarui di sini. Tanyakan keadaan jalurnya langsung kepada '
                        .'pengelola kawasan sebelum merencanakan keberangkatan.',
                    'duration_requires_overnight' => 'Durasi jalur tidak sesuai dengan rencana pulang hari.',
                    'elevation_gain_above_goal_limit' => 'Elevation gain jalur melebihi batas yang Anda tentukan.',
                    default => $rule,
                };
            }
        }

        // Satu kalimat pembuka, lalu nama barangnya saja. Mengulang "Item persiapan
        // kritis belum dikonfirmasi" dua belas kali mengubur yang justru perlu dibaca.
        $kritis = $preparationState['critical_outstanding'] ?? [];

        if ($kritis !== []) {
            $outstanding[] = sprintf(
                '%d item persiapan kritis belum dikonfirmasi: %s.',
                count($kritis),
                implode(', ', $kritis)
            );
        }

        if (($preparationState['completion_percent'] ?? 0) < 100) {
            $outstanding[] = sprintf('Persiapan baru %d%% dikonfirmasi.', $preparationState['completion_percent'] ?? 0);
        }

        // Pengguna harus tahu bahwa yang menahan kesiapannya adalah data cuaca, bukan
        // sesuatu yang ia lupa kerjakan (PRD §47).
        if (($weather['available'] ?? false) === false) {
            $outstanding[] = 'Data cuaca area sekitar jalur belum tersedia, sehingga kondisi terkini belum dapat diperiksa.';
        } elseif (($weather['freshness'] ?? null) === FreshnessState::STALE->value) {
            $outstanding[] = 'Data cuaca belum berhasil diperbarui, sehingga kondisi terkini belum dapat dipastikan.';
        }

        if ($state === ReadinessState::READY) {
            $reasons[] = 'Route fit, persiapan, status resmi, dan kondisi terkini saat ini tidak menunjukkan isu yang belum selesai.';
        }

        return [
            'state' => [$state->label()],
            'reasons' => $reasons,
            'outstanding' => $outstanding,
            'condition_warnings' => array_values(array_merge($routeWarnings, $dataWarnings)),
            'route_warnings' => $routeWarnings,
            'data_warnings' => $dataWarnings,
            // W4-6a (putaran perbaikan 2): SEMUA sebab NEEDS_PREPARATION yang berlaku sekarang,
            // dihitung dari masukan yang SAMA dengan determineState() (bukan snapshot bercache),
            // bukan hanya cabang pertama yang menentukan keadaan. Satu sumber untuk HalamanKesiapan,
            // tanpa salinan logika cabang di kelas lain (n-2).
            'preparation_causes' => $state === ReadinessState::NEEDS_PREPARATION
                ? $this->needsPreparationCauses($preparationState, $fit, $profilLengkap, $status, $conditions)
                : [],
        ];
    }

    /**
     * Semua sebab NEEDS_PREPARATION yang berlaku, bukan hanya yang pertama ditemukan (W4-6a,
     * putaran perbaikan 2): setiap cabang determineState() yang terpenuhi sendiri sudah cukup
     * menahan keadaan, jadi SEMUANYA adalah sebab dan disebut bersama, berurutan menurut
     * urutan cabang determineState(). Status dibaca dari parameter $status yang sama dengan
     * determineState() (bukan snapshot bercache 300 detik di conditionSnapshot), supaya sebab
     * UNKNOWN tidak pernah keliru di dalam jendela cache (n-2).
     *
     * Bila seluruh butir ditandai Tidak berlaku, completion_percent jatuh ke 0 tanpa ada
     * butir kritis maupun butir lain yang "belum" (TripPlan::preparationCompletionPercent()
     * mengosongkan $applicable), sehingga kasus ini perlu kalimatnya sendiri, bukan jatuh
     * tanpa sebab apa pun ke kalimat cadangan lama (probe R4-D).
     *
     * @param  array<string, mixed>  $preparationState
     * @param  array<string, mixed>  $conditions
     * @return array<int, string>
     */
    private function needsPreparationCauses(
        array $preparationState,
        RouteFitResult $fit,
        bool $profilLengkap,
        OfficialStatusValue $status,
        array $conditions,
    ): array {
        $sebab = [];

        $totalItems = (int) ($preparationState['total_items'] ?? 0);
        $notApplicable = (int) ($preparationState['not_applicable'] ?? 0);
        $kritisBelum = count($preparationState['critical_outstanding'] ?? []);
        $lainBelum = (int) ($preparationState['not_confirmed'] ?? 0);
        $completion = (int) ($preparationState['completion_percent'] ?? 0);

        if ($totalItems === 0) {
            // MIN-1 (putaran perbaikan 3): trip tanpa satu pun butir persiapan (templat
            // belum sempat ditanam, atau lingkungan tanpa seeder) jatuh ke completion_percent
            // 0 lewat TripPlan::preparationCompletionPercent() dan membuat determineState()
            // kembali ke NEEDS_PREPARATION (:167), tetapi ketiga predikat di bawah semuanya
            // butuh butir untuk bisa bernilai true -- tanpa cabang ini, sebabnya kosong dan
            // kalimatnya jatuh ke cadangan lama "Masih ada yang perlu diperiksa sebelum
            // berangkat." (probe R2 "kosong | cocok | OPEN").
            $sebab[] = 'Daftar persiapan belum ada.';
        } elseif ($notApplicable === $totalItems) {
            $sebab[] = 'Seluruh butir persiapan ditandai Tidak berlaku.';
        } elseif ($kritisBelum > 0) {
            $sebab[] = $kritisBelum.' butir kritis belum dikonfirmasi.';
        } elseif ($completion < 100 && $lainBelum > 0) {
            $sebab[] = 'Butir kritis sudah dikonfirmasi; '.$lainBelum.' butir lain belum dikonfirmasi.';
        }

        // I4: label KURANG_COCOK dari profil yang belum lengkap sudah null di routeFitSnapshot,
        // tetapi $fit->label di sini masih mentah, jadi gerbangnya diulang secara eksplisit.
        if ($profilLengkap && $fit->label === RouteFitLabel::KURANG_COCOK) {
            $sebab[] = 'Kecocokan jalur untuk rencana ini Kurang cocok.';
        }

        if ($status === OfficialStatusValue::UNKNOWN) {
            $sebab[] = 'Status resmi jalur ini belum diketahui.';
        }

        if ($this->hasUnresolvedConditions($conditions)) {
            $sebab[] = 'Ada peringatan kondisi yang perlu ditinjau.';
        }

        return $sebab;
    }
}
