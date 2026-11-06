<?php

namespace App\Services\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\Readiness\ReadinessAssessment;
use App\Services\ReadinessService;
use App\Support\Timezone;
use Illuminate\Support\Facades\DB;

/**
 * Konfirmasi tinjauan (FR-10, langkah inti North Star §63).
 *
 * Readiness adalah dukungan keputusan, bukan izin keselamatan (R-014): yang dicatat di sini
 * hanya bahwa pendaki sudah memeriksa, bukan bahwa pendakiannya aman.
 */
class PreDepartureCheck
{
    public function __construct(
        private readonly ReadinessService $readiness,
        private readonly AnalyticsRecorder $analytics,
    ) {}

    /**
     * Waktu tinjauan dalam zona gunungnya, dengan format jam yang sama dengan seluruh halaman Cek
     * (M3 tinjauan akhir W4).
     */
    public static function waktuTinjauan(TripPlan $trip, ReadinessCheck $tinjauan): string
    {
        return Timezone::display($tinjauan->updated_at, Timezone::forTrail($trip->trail), 'j M Y H.i') ?? 'sebelumnya';
    }

    /**
     * Alasan tinjauan tidak dapat dicatat untuk trip ini sekarang, atau null bila boleh. Satu
     * sumber untuk confirm() (penolakan POST) DAN halaman (`alasanTanpaTinjauan` di
     * ReadinessController), supaya halaman tidak pernah menulis alasan yang berbeda dari yang
     * akan diberikan POST yang sama.
     *
     * Urutannya: tinjauan yang sudah tercatat (TripPlan::tinjauanTercatat(), predikat yang sama
     * dengan langkah Cek di ruang kerja dan kartu indeks), lalu status yang tidak dapat
     * bertransisi (trip yang berlangsung, selesai, batal, atau draf tidak akan pernah dapat
     * ditinjau sekarang, apa pun penilaiannya), lalu rencana yang tanggalnya sudah lewat (ruling
     * W4-FK: tinjauan untuk tanggal yang lewat bukan tinjauan sebelum berangkat), lalu rencana
     * yang tidak disarankan beserta sebabnya.
     */
    public function alasanPenolakan(TripPlan $trip, ReadinessAssessment $assessment): ?string
    {
        $tinjauan = $trip->tinjauanTercatat();

        if ($tinjauan !== null) {
            return 'Tinjauan sudah tercatat '.self::waktuTinjauan($trip, $tinjauan).'.';
        }

        if (! $trip->status->canTransitionTo(TripStatus::READY_FOR_DEPARTURE)) {
            return sprintf('Tinjauan tidak dapat dicatat karena trip ini berstatus %s.', $trip->status->label());
        }

        if ($trip->tanggalRencanaLewat()) {
            return 'Tinjauan tidak dapat dicatat karena tanggal rencana sudah lewat. Ubah rencana dulu.';
        }

        // Penolakan yang tidak memberi tahu apa yang menahannya hanya memindahkan kebuntuan
        // dari sistem ke pendaki, tepat saat ia hendak memeriksa rencananya.
        if ($assessment->state === ReadinessState::NOT_RECOMMENDED) {
            $alasan = $assessment->explanation['reasons'] ?? [];

            return $alasan === []
                ? 'Tinjauan tidak dapat dicatat karena rencana ini tidak disarankan.'
                : 'Tinjauan tidak dapat dicatat karena rencana ini tidak disarankan. '.implode(' ', array_unique($alasan));
        }

        return null;
    }

    /**
     * Mencatat tinjauan, atau mengembalikan kalimat penolakannya.
     *
     * Baris trip dikunci sebelum keadaannya dibaca, dan seluruh periksa-lalu-tulis berada dalam
     * satu transaksi (M5 tinjauan akhir W4). Dua POST hampir bersamaan (dua tab, kirim ulang
     * jaringan) dengan begitu tidak dapat sama-sama lolos: yang kedua menunggu kunci, lalu
     * membaca status Siap berangkat yang ditulis yang pertama dan ditolak. Hasilnya satu baris
     * terkonfirmasi dan satu event PRE_DEPARTURE_CHECK_COMPLETED (funnel §62, metrik §63), dan
     * tidak ada keadaan setengah jadi. Kuncinya sungguhan di PostgreSQL; SQLite tidak punya
     * kunci baris, jadi buktinya di tests/Spatial (R-024).
     *
     * @return string|null kalimat penolakan, atau null bila tinjauan tercatat
     */
    public function confirm(TripPlan $trip, User $user): ?string
    {
        return DB::transaction(function () use ($trip, $user): ?string {
            $terkunci = TripPlan::query()->whereKey($trip->getKey())->lockForUpdate()->firstOrFail();

            if ($trip->relationLoaded('trail')) {
                $terkunci->setRelation('trail', $trip->trail);
            }

            $assessment = $this->readiness->compute($terkunci);
            $penolakan = $this->alasanPenolakan($terkunci, $assessment);

            if ($penolakan !== null) {
                return $penolakan;
            }

            $this->readiness->record($terkunci, $assessment, dikonfirmasi: true);
            $terkunci->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);

            $this->analytics->record(AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED, $user, [
                'trip_plan_id' => $terkunci->id,
            ]);

            return null;
        });
    }
}
