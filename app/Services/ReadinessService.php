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

        $fit = $this->routeFit->evaluate($trip->user, $trip->hikingGoal, $trip->trail);
        $preparationState = $this->preparation->state($trip);
        $status = $this->officialStatus->effectiveStatusForTrail($trip->trail);
        $conditions = $this->conditions->forTrail($trip->trail);

        // Yang dinilai di sini adalah sebuah rencana, jadi status pada tanggal rencananya
        // sama menentukannya dengan status hari ini. Penutupan besar di Indonesia bersifat
        // tahunan dan diumumkan jauh hari, sehingga rencana untuk Januari dapat jatuh ke
        // penutupan yang sudah tercatat sekarang.
        $statusTanggalRencana = $trip->planned_date
            ? $this->officialStatus->effectiveStatusForTrailOn($trip->trail, $trip->planned_date)
            : $status;

        $state = $this->determineState($fit, $status, $preparationState, $conditions, $statusTanggalRencana);

        return new ReadinessAssessment(
            state: $state,
            routeFitSnapshot: [
                'label' => $fit->label?->value,
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
                $statusTanggalRencana,
                $trip->planned_date,
            ),
        );
    }

    /**
     * Menyimpan hasil perhitungan sebagai satu baris ReadinessCheck.
     */
    public function record(TripPlan $trip, ?ReadinessAssessment $assessment = null): ReadinessCheck
    {
        $assessment ??= $this->compute($trip);

        return ReadinessCheck::create(array_merge(
            ['trip_plan_id' => $trip->id],
            $assessment->toAttributes()
        ));
    }

    /**
     * Check tersimpan terakhir untuk trip ini, atau null bila belum pernah dicatat.
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

        if ($fit->label === RouteFitLabel::KURANG_COCOK || $status === OfficialStatusValue::UNKNOWN) {
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
        $conditionWarnings = $conditions['warnings'] ?? [];
        $weather = $conditions['weather_context'] ?? [];

        if ($status === OfficialStatusValue::CLOSED) {
            $reasons[] = 'Status resmi jalur ini tercatat TUTUP.';
        }

        if ($status === OfficialStatusValue::UNKNOWN) {
            $reasons[] = 'Status resmi jalur belum dapat dipastikan, sehingga kondisi terkini belum terverifikasi.';
        }

        if (! $fit->eligible) {
            foreach ($fit->failedRules as $rule) {
                $reasons[] = match ($rule) {
                    'official_status_closed' => 'Jalur tidak tersedia karena status resmi TUTUP.',
                    'trail_not_published' => 'Data jalur ini belum dipublikasikan.',
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
            'condition_warnings' => $conditionWarnings,
        ];
    }
}
