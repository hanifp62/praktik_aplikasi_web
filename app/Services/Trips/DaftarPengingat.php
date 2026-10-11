<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PermitService;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Isi lembar Pengingat dari lonceng Beranda (spec v3 bagian 5.1; rencana induk W2 butir 5;
 * ruling W2-R2): pengingat surel H-3 dan H-1 yang masih terjadwal, dan tenggat pemesanan izin,
 * keduanya untuk trip mendatang milik pengguna sendiri. Notifikasi dorong tidak ada (Q6), jadi
 * lembar ini daftar, bukan kotak masuk.
 *
 * Biayanya tetap: trip mendatang beserta jalur, gunung, dan pengingat yang menunggu (empat
 * kueri eager), ditambah satu kueri aturan izin untuk semua jalurnya sekaligus
 * (PermitService::requirementsForTrails), untuk paling banyak TRIP_MAKS trip terdekat. Waktu
 * pengingat ditulis dalam zona gunungnya dengan penanda WIB, WITA, atau WIT; tanggal izin
 * dihitung dari tanggal rencana, jadi tidak bergantung zona.
 */
class DaftarPengingat
{
    private const TRIP_MAKS = 10;

    private const FORMAT_WAKTU = 'l, d M Y H.i';

    public function __construct(private PermitService $izin, private TenggatIzin $tenggat) {}

    /**
     * @return array{
     *     pengingat: list<array{id: int, trip: string, jenis: string, waktu: string, url: string}>,
     *     tenggatIzin: list<array{tripId: int, trip: string, keadaan: string, kalimat: string, pengelola: string, bookingUrl: string|null, url: string}>
     * }
     */
    public function untuk(User $user): array
    {
        $trips = TripPlan::query()
            ->whereBelongsTo($user)
            ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::READY_FOR_DEPARTURE->value])
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->with([
                'trail.mountain',
                // Predikat yang sama dengan Tenggat terdekat Beranda (ruling W5-FE).
                'reminders' => fn ($query) => $query->akanDatang()->orderBy('dijadwalkan_untuk'),
            ])
            ->orderBy('planned_date')
            ->orderBy('id')
            ->limit(self::TRIP_MAKS)
            ->get();

        $aturan = $this->izin->requirementsForTrails(new EloquentCollection($trips->pluck('trail')->filter()->unique('id')->values()->all()));

        $pengingat = [];
        $tenggat = [];

        foreach ($trips as $trip) {
            $zona = Timezone::forTrail($trip->trail);
            $url = route('trips.show', $trip, false);

            foreach ($trip->reminders as $satu) {
                $pengingat[] = [
                    'urutan' => $satu->dijadwalkan_untuk->getTimestamp(),
                    'id' => $satu->id,
                    'trip' => $trip->name,
                    'jenis' => TripReminderScheduler::label($satu->jenis),
                    'waktu' => (string) Timezone::display($satu->dijadwalkan_untuk, $zona, self::FORMAT_WAKTU),
                    'url' => $url,
                ];
            }

            $butir = $this->tenggatIzin($trip, $aturan[$trip->trail_id] ?? null, $url);

            if ($butir !== null) {
                $tenggat[] = $butir;
            }
        }

        usort($pengingat, fn (array $a, array $b) => $a['urutan'] <=> $b['urutan']);
        usort($tenggat, fn (array $a, array $b) => ($a['urutan'] ?? PHP_INT_MAX) <=> ($b['urutan'] ?? PHP_INT_MAX));

        return [
            'pengingat' => array_map(fn (array $p) => array_diff_key($p, ['urutan' => true]), $pengingat),
            'tenggatIzin' => array_map(fn (array $t) => array_diff_key($t, ['urutan' => true]), $tenggat),
        ];
    }

    /**
     * Satu kalimat tentang jendela pemesanan izin trip ini, atau null bila tidak ada aturan
     * izin yang tercatat. Kalimat dan tanggal patokannya milik TenggatIzin, yang juga dibaca
     * Tenggat terdekat Beranda (W5).
     *
     * @return array{urutan: int|null, tripId: int, trip: string, keadaan: string, kalimat: string, pengelola: string, bookingUrl: string|null, url: string}|null
     */
    private function tenggatIzin(TripPlan $trip, ?PermitRequirement $aturan, string $url): ?array
    {
        $jendela = $this->tenggat->untuk($trip, $aturan);

        if ($jendela === null || $aturan === null) {
            return null;
        }

        return [
            'urutan' => $jendela['tanggal']?->getTimestamp(),
            'tripId' => $trip->id,
            'trip' => $trip->name,
            'keadaan' => $jendela['keadaan'],
            'kalimat' => $jendela['kalimat'],
            'pengelola' => $aturan->authority,
            // Tautan sistem resmi (R-009: Memount menautkan, tidak menjual izin). Sudah ikut
            // $aturan yang dimuat requirementsForTrails, jadi tanpa kueri tambahan (Ruling
            // W2-6e #4).
            'bookingUrl' => $aturan->booking_url,
            'url' => $url,
        ];
    }
}
