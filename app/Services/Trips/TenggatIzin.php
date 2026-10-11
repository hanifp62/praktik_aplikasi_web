<?php

namespace App\Services\Trips;

use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Services\PermitService;
use Illuminate\Support\Carbon;

/**
 * Tenggat jendela pemesanan izin satu trip. Lembar Pengingat (DaftarPengingat) dan Tenggat
 * terdekat Beranda (TenggatTrip, W5) membaca judul, tanggal, dan kalimat yang sama dari sini,
 * supaya keduanya tidak pernah berbeda. Aritmetika jendelanya tetap milik PermitService; di
 * sini hanya dipilih tanggal mana yang disebut. Batas yang tidak tercatat disebut belum
 * tercatat, tidak ditebak (R-007).
 */
final class TenggatIzin
{
    private const FORMAT_TANGGAL = 'l, d M Y';

    public function __construct(private readonly PermitService $izin) {}

    /**
     * Null bila tidak ada aturan izin yang tercatat atau tanggal tripnya sudah lewat. Tanpa
     * tanggal, catatan menyebut apa yang belum tercatat.
     *
     * @return array{keadaan: string, judul: string, hari: int|null, tanggal: Carbon|null, catatan: string|null, kalimat: string}|null
     */
    public function untuk(TripPlan $trip, ?PermitRequirement $aturan): ?array
    {
        if ($trip->trail === null || $aturan === null) {
            return null;
        }

        $jendela = $this->izin->bookingWindowFor($trip->trail, $trip->planned_date, $aturan);

        if ($jendela === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;

        [$judul, $hari, $catatan] = match (true) {
            $jendela['state'] === PermitService::BOOKING_BELUM_DIBUKA && $buka !== null => ['Pemesanan izin dibuka', $buka, null],
            $jendela['state'] === PermitService::BOOKING_DITUTUP && $tutup !== null => ['Pemesanan izin sudah ditutup', $tutup, null],
            // Tanggal tutup tercatat tetapi tanggal buka tidak: tidak ditulis seolah sudah dibuka
            // (R-007, ruling W4-FE), hari terakhirnya tetap disebut.
            $jendela['state'] === PermitService::BOOKING_BUKA_BELUM_TERCATAT && $tutup !== null => ['Tanggal buka pemesanan izin belum tercatat; pemesanan ditutup', $tutup, null],
            $tutup !== null => ['Pemesanan izin ditutup', $tutup, null],
            // Tanggal buka tercatat, sedang berjalan, hanya tutupnya yang belum tercatat:
            // itu fakta yang diketahui, bukan tebakan.
            $buka !== null => ['Pemesanan izin sedang dibuka', null, 'Tanggal penutupannya belum tercatat.'],
            // Tidak ada apa pun yang tercatat: mengklaim "sedang dibuka" di sini adalah
            // klaim tanpa data, bukan sekadar tanggal yang belum tercatat (R-007, Ruling
            // W2-6a #6). Sejak ruling W4-FE PermitService sendiri menandai jendela seperti ini
            // BUKA_BELUM_TERCATAT, bukan TERBUKA.
            default => ['Jendela pemesanan izin belum tercatat', null, 'Periksa jendelanya di sistem resmi.'],
        };

        $tanggal = $hari === null ? null : $trip->planned_date->copy()->subDays($hari);

        return [
            'keadaan' => $jendela['state'],
            'judul' => $judul,
            'hari' => $hari,
            'tanggal' => $tanggal,
            'catatan' => $catatan,
            'kalimat' => match (true) {
                $tanggal !== null => sprintf('%s %s (H-%d).', $judul, $tanggal->translatedFormat(self::FORMAT_TANGGAL), $hari),
                $buka !== null => $judul.'. '.$catatan,
                default => $judul.'; periksa sistem resmi.',
            },
        ];
    }
}
