<?php

namespace App\Services\Dashboard;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Posisi pengguna dalam alur, untuk dasbor.
 *
 * Urutannya adalah urutan kepentingan, bukan urutan alur: pendakian yang sedang
 * berjalan mengalahkan apa pun, lalu trip terdekat yang belum lewat, baru ajakan
 * memulai sesuatu yang baru. Semua tanggal dan hitung mundur diformat di sini supaya
 * klien tidak pernah menghitung zona waktu sendiri.
 */
final class RingkasanDasbor
{
    public function __construct(private User $user) {}

    /**
     * @return array{keadaan: string, trip: array<string, mixed>|null}
     */
    public function props(): array
    {
        if (! $this->user->hasCompletedProfile()) {
            return ['keadaan' => 'profil', 'trip' => null];
        }

        $berjalan = $this->tripBerjalan();

        if ($berjalan !== null) {
            return ['keadaan' => 'berjalan', 'trip' => $this->ringkas($berjalan)];
        }

        $terdekat = $this->tripTerdekat();

        if ($terdekat !== null) {
            return ['keadaan' => 'terdekat', 'trip' => $this->ringkas($terdekat)];
        }

        return ['keadaan' => 'mulai', 'trip' => null];
    }

    /**
     * Pendaki yang membuka aplikasi di jalur butuh mode pendakian, bukan ajakan
     * merencanakan trip lain.
     */
    private function tripBerjalan(): ?TripPlan
    {
        return $this->dasar()
            ->where('status', TripStatus::IN_PROGRESS->value)
            ->orderBy('planned_date')
            ->first();
    }

    /**
     * Trip terdekat yang belum lewat. Yang sudah selesai atau dibatalkan tidak ikut,
     * supaya dasbor tidak tersangkut pada perjalanan yang sudah berakhir.
     *
     * now() menyelesaikan "hari ini" menurut zona aplikasi (UTC), dan selama tujuh jam
     * setiap hari tanggal UTC masih kemarin bagi WIB, sehingga trip yang sudah lewat
     * menurut WIB tetap lolos dan, karena diurutkan naik, menyembunyikan trip yang
     * sungguh berangkat hari ini. Forward-looking, jadi dipakai WIB
     * (earliestDateInIndonesia): tanggalnya selalu yang terkecil di antara ketiga zona.
     */
    private function tripTerdekat(): ?TripPlan
    {
        return $this->dasar()
            ->whereIn('status', [
                TripStatus::DRAFT->value,
                TripStatus::PLANNED->value,
                TripStatus::READY_FOR_DEPARTURE->value,
            ])
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->orderBy('planned_date')
            ->first();
    }

    /**
     * @return Builder<TripPlan>
     */
    private function dasar(): Builder
    {
        return TripPlan::query()
            ->where('user_id', $this->user->id)
            ->with('trail.mountain', 'latestReadinessCheck');
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkas(TripPlan $trip): array
    {
        $catatan = match (true) {
            $trip->trailIsWithdrawn() => ['jenis' => 'ditarik', 'label' => null],
            $trip->readinessIsStale() => ['jenis' => 'basi', 'label' => null],
            $trip->latestReadinessCheck !== null => ['jenis' => 'terakhir', 'label' => $trip->latestReadinessCheck->computed_state->label()],
            default => ['jenis' => 'belum', 'label' => null],
        };

        return [
            'id' => $trip->id,
            'name' => $trip->name,
            'trail' => $trip->trail?->name,
            'mountain' => $trip->trail?->mountain?->name,
            'tanggal' => $trip->planned_date->translatedFormat('l, d F Y'),
            'hitungMundur' => $this->hitungMundur($trip),
            'catatan' => $catatan,
            'urls' => [
                'hike' => route('trips.hike', $trip),
                'show' => route('trips.show', $trip),
                'readiness' => route('trips.readiness', $trip),
                'preparation' => route('trips.preparation', $trip),
            ],
        ];
    }

    /**
     * Jarak hari ke keberangkatan, terpisah antara angka dan katanya supaya angkanya
     * dapat dibuat sebesar perannya. Hari ini, besok, dan tanggal yang sudah lewat tidak
     * punya angka yang pantas dibesarkan: "0 hari lagi" bukan cara orang bicara.
     *
     * Lantainya WIB (earliestDateInIndonesia), karena now() mentah membaca tanggal
     * kemarin selama tujuh jam setiap hari dan kartu berkata "besok" untuk
     * keberangkatan yang sesungguhnya hari ini.
     *
     * @return array{angka: ?int, kata: string}
     */
    private function hitungMundur(TripPlan $trip): array
    {
        $hariIni = Carbon::parse(Timezone::earliestDateInIndonesia());

        $hari = (int) $hariIni->diffInDays($trip->planned_date->copy()->startOfDay(), false);

        return match (true) {
            $hari < 0 => ['angka' => null, 'kata' => 'Tanggalnya sudah lewat'],
            $hari === 0 => ['angka' => null, 'kata' => 'Berangkat hari ini'],
            $hari === 1 => ['angka' => null, 'kata' => 'Berangkat besok'],
            default => ['angka' => $hari, 'kata' => 'hari lagi'],
        };
    }
}
