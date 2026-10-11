<?php

namespace App\Services\Trips;

use App\Models\TripPlan;
use App\Support\Timezone;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Kepala satu trip untuk ruang kerja, persiapan, dan cek kesiapan (spec v3 bagian 6.10,
 * prototype 09): jenis, sisa hari, nama, tanggal, jam, peserta, dan tautan "Ubah". Seluruh
 * teks diformat di sini, jadi ketiga halaman menulis rencana yang sama dengan cara yang sama.
 *
 * Status trip hanya disebut untuk trip yang sudah dimulai, selesai, atau dibatalkan. Trip
 * yang belum dimulai cukup dibaca dari H-n dan langkahnya: label "Siap berangkat" di samping
 * nama trip terbaca sebagai jaminan, dan itu yang dilarang R-014. Rencana yang belum dimulai
 * tetapi tanggalnya sudah lewat tidak menghitung mundur dan menyebut itu (ruling W4-1c).
 */
final class KepalaTrip
{
    /** Penanda rencana yang belum dimulai tetapi tanggalnya sudah lewat (ruling W4-1c). */
    public const TANGGAL_LEWAT = 'Tanggal rencana sudah lewat';

    public function __construct(private readonly TripLifecycle $lifecycle) {}

    /**
     * Membutuhkan relasi trail.mountain sudah dimuat.
     *
     * @return array{id: int, nama: string, jalur: string, gunung: string, jenis: string, hari: string|null, lewat: string|null, tanggal: string, jam: string|null, peserta: string|null, keadaan: string|null, ubah: string|null}
     */
    public function untuk(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $belumDimulai = $trip->status->belumDimulai();

        return [
            'id' => $trip->id,
            'nama' => $trip->name,
            'jalur' => $trip->trail->name,
            'gunung' => $trip->trail->mountain->name,
            'jenis' => $trip->trip_type->labelPendek(),
            'hari' => $belumDimulai ? self::hariMenuju($trip->planned_date, $zona) : null,
            'lewat' => $trip->tanggalRencanaLewat() ? self::TANGGAL_LEWAT : null,
            'tanggal' => self::rentang($trip->planned_date, $trip->end_date),
            // Jam ini rencana pendaki dalam waktu setempat gunungnya, bukan instan UTC, jadi
            // tidak dikonversi; penandanya tetap disebut (§93).
            'jam' => $trip->start_time ? Carbon::parse($trip->start_time)->format('H.i').' '.Timezone::label($zona) : null,
            'peserta' => $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
            'keadaan' => $belumDimulai ? null : $trip->status->label(),
            'ubah' => $this->lifecycle->editRefusal($trip) === null ? route('trips.edit', $trip) : null,
        ];
    }

    /**
     * "H-15", "Hari H", atau null untuk tanggal yang sudah lewat, dihitung dari tanggal hari ini
     * di zona gunungnya (Timezone::hariSampai, hitungan yang sama dengan
     * TripPlan::tanggalRencanaLewat()).
     */
    public static function hariMenuju(CarbonInterface $tanggal, string $zona): ?string
    {
        $hari = Timezone::hariSampai($tanggal, $zona);

        return match (true) {
            $hari < 0 => null,
            $hari === 0 => 'Hari H',
            default => 'H-'.$hari,
        };
    }

    /**
     * Tanggal rencana; rentang ditulis dengan kata "sampai" (spec v3 bagian 8.8), tahunnya
     * sekali di akhir.
     */
    public static function rentang(CarbonInterface $mulai, ?CarbonInterface $selesai): string
    {
        if ($selesai === null || $selesai->isSameDay($mulai)) {
            return $mulai->translatedFormat('D, j M Y');
        }

        return $mulai->translatedFormat($mulai->year === $selesai->year ? 'D, j M' : 'D, j M Y').' sampai '.$selesai->translatedFormat('D, j M Y');
    }
}
