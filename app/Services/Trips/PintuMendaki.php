<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Support\Arr;

/**
 * Pintu tab Mendaki tanpa pendakian berlangsung (spec v3 bagian 6.12; rencana induk W6 butir 1).
 *
 * Trip terdekat yang dapat dimulai mendapat pratinjau hari-H: kapan, keadaan paket luring,
 * bagaimana lokasi dipakai, dan apakah rekam jejak menyala, sebelum satu tombol "Mulai
 * pendakian". Trip lain yang dapat dimulai didaftar di bawahnya, dengan gunungnya karena nama
 * jalur tidak unik (R-001, putaran perbaikan 1 M-5). Status trip dan vonis kesiapan tidak
 * disebut (ruling W4-C4 untuk Mendaki, W6-R9): cek kesiapan hanya disebut tanggalnya, dan
 * tanggal rencana yang sudah lewat di zona gunungnya ikut disebut untuk hari-H maupun setiap
 * baris trip lain (putaran perbaikan 1 M-7, putaran perbaikan 2 N-2). Kalimat kartu rekam jejak
 * tidak mengulang waktu kirim yang sudah disebut `lokasi` (putaran perbaikan 2 S-1); angka batas
 * trip di `potong.kalimat` diambil dari `BATAS_TRIP`, bukan ditulis mati (putaran perbaikan 2
 * N-3).
 *
 * Kueri tetap berapa pun jumlah trip: satu kueri trip berbatas dengan relasi yang dimuat
 * sekaligus. Batasnya diberi tahu, bukan dipotong diam-diam (putaran perbaikan 1 M-1): sebaris
 * di atas batas diambil untuk mendeteksi pemotongan sebelum baris ekstra itu dibuang.
 */
final class PintuMendaki
{
    private const BATAS_TRIP = 10;

    public function __construct(private readonly KepalaTrip $kepala) {}

    /**
     * @return array{hariH: array<string, mixed>|null, lain: array<int, array<string, mixed>>, lokasi: string, jejak: array{menyala: bool, kalimat: string, url: string}, paket: array{aktif: bool, kalimat: string}, potong: array{terpotong: bool, kalimat: string, url: string}}
     */
    public function untuk(User $pengguna): array
    {
        $trips = TripPlan::query()
            ->whereBelongsTo($pengguna)
            ->whereIn('status', [TripStatus::READY_FOR_DEPARTURE->value, TripStatus::PLANNED->value])
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->with('trail.mountain', 'latestReadinessCheck')
            ->orderBy('planned_date')
            ->orderByRaw('case when status = ? then 0 else 1 end', [TripStatus::READY_FOR_DEPARTURE->value])
            ->orderBy('id')
            ->limit(self::BATAS_TRIP + 1)
            ->get();

        $terpotong = $trips->count() > self::BATAS_TRIP;

        if ($terpotong) {
            $trips = $trips->slice(0, self::BATAS_TRIP);
        }

        $rekam = (bool) $pengguna->preference?->record_track;
        $paket = (bool) config('hiking.field_pack.enabled');

        return [
            'hariH' => $trips->isEmpty() ? null : $this->pratinjau($trips->first()),
            'lain' => $trips->slice(1)->map(fn (TripPlan $trip) => [
                'id' => $trip->id,
                'nama' => $trip->name,
                'jalur' => $trip->trail->name,
                'gunung' => $trip->trail->mountain->name,
                'tanggal' => KepalaTrip::rentang($trip->planned_date, $trip->end_date),
                'hari' => KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)),
                'lewat' => $trip->tanggalRencanaLewat() ? KepalaTrip::TANGGAL_LEWAT : null,
                'urls' => ['mulai' => route('trips.start', $trip), 'ruangKerja' => route('trips.show', $trip)],
            ])->values()->all(),
            // Lokasi baru diminta di mode lapangan (PRD §145.1), bukan di tab ini: kalimat ini
            // tidak boleh menyiratkan lokasi sudah diminta sekarang. Cabang menyala menyebut
            // kapan titik posisi dikirim, sejalan dengan kalimat rekam jejak di bawah, supaya
            // kedua kalimat yang bertetangga tidak saling membantah (putaran perbaikan 1 I-1).
            'lokasi' => $rekam
                ? 'Lokasi baru diminta sesudah pendakian dimulai dan dipakai di perangkat ini. Rekam jejak menyala: titik posisi disimpan di perangkat dan baru dikirim sesudah pendakian selesai atau dibatalkan, saat ada sinyal.'
                : 'Lokasi baru diminta sesudah pendakian dimulai, dipakai di perangkat ini, dan tidak dikirim ke server.',
            'jejak' => [
                'menyala' => $rekam,
                'kalimat' => $rekam
                    ? 'Rekam jejak menyala. Jejaknya dapat Anda hapus di ringkasan pendakian.'
                    : 'Rekam jejak mati. Nyalakan di Profil bila Anda ingin menyimpan jejak pendakian ini.',
                'url' => route('profile'),
            ],
            'paket' => [
                'aktif' => $paket,
                'kalimat' => $paket
                    ? 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.'
                    : 'Paket lapangan luring belum dibuka. Buka mode lapangan selagi ada sinyal dan biarkan tetap terbuka, lalu bawa peta cetak atau perangkat lain sebagai cadangan.',
            ],
            'potong' => [
                'terpotong' => $terpotong,
                'kalimat' => 'Menampilkan '.self::BATAS_TRIP.' trip terdekat. Trip lainnya ada di tab Trip.',
                'url' => route('trips.index'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pratinjau(TripPlan $trip): array
    {
        $cek = $trip->latestReadinessCheck?->computed_at;

        return [
            ...Arr::only($this->kepala->untuk($trip), ['id', 'nama', 'jalur', 'gunung', 'jenis', 'hari', 'lewat', 'tanggal', 'jam', 'peserta']),
            'cek' => $cek === null
                ? 'Cek kesiapan belum dilakukan untuk trip ini.'
                : 'Cek kesiapan terakhir '.$cek->copy()->setTimezone(Timezone::forTrail($trip->trail))->translatedFormat('j M Y').'.',
            'urls' => [
                'mulai' => route('trips.start', $trip),
                'ruangKerja' => route('trips.show', $trip),
                'cek' => route('trips.readiness', $trip),
                'lapangan' => route('trips.hike', $trip),
            ],
        ];
    }
}
