<?php

namespace App\Services\Dashboard;

use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\Trips\TenggatIzin;
use App\Services\Trips\TripReminderScheduler;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Tenggat terdekat trip mendatang di Beranda (spec v3 bagian 6.1, fase B2, prototype 01;
 * rencana induk W5 butir 2): jendela pemesanan izin (TenggatIzin, sumber yang sama dengan
 * lembar Pengingat), hari prakiraan BMKG untuk tanggal mulai terbit, dan pengingat surel yang
 * masih menunggu, dari yang paling dekat.
 *
 * Hari dan jam menurut zona gunungnya; penandanya (WIB, WITA, atau WIT) dikirim sekali untuk
 * seluruh daftar. Tahun ditulis bila berbeda dari tahun berjalan (W3-1g). Tenggat izin hilang
 * begitu butir izin di persiapan dikonfirmasi atau ditandai tidak berlaku, dan pengingat hari
 * pemesanan dibuka ditulis di baris tenggat itu, bukan sebagai baris sendiri. Jendela yang
 * tidak tercatat tetap disebut, tanpa tanggal, di akhir daftar (R-007).
 *
 * Pengingat trip dibaca dari relasi reminders yang dimuat lewat TripReminder::akanDatang()
 * (RingkasanDasbor::tripMendatang()), predikat yang sama dengan lembar Pengingat (ruling
 * W5-FE): surel yang jamnya sudah lewat tidak pernah disebut tenggat.
 */
final class TenggatTrip
{
    public function __construct(private readonly TenggatIzin $tenggatIzin) {}

    /**
     * @return array{zona: string, baris: list<array{kapan: array{tanggal: string, bulan: string, tahun: string|null, lengkap: string}|null, judul: string, keterangan: string, tautan: array{label: string, href: string}|null, peringatan: bool}>}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        $trip = $konteks->trip;

        if ($trip === null) {
            return null;
        }

        $zona = Timezone::forTrail($trip->trail);
        $tenggat = [...$this->izin($trip, $konteks->aturanIzin, $zona), ...$this->prakiraan($trip, $zona), ...$this->pengingat($trip, $zona)];

        if ($tenggat === []) {
            return null;
        }

        usort($tenggat, fn (array $a, array $b) => $a['urutan'] <=> $b['urutan']);

        return [
            'zona' => Timezone::label($zona),
            'baris' => array_map(fn (array $t) => array_diff_key($t, ['urutan' => true]), $tenggat),
        ];
    }

    /**
     * Janji "Pengingat surel dikirim pukul ..." hanya ditulis bila baris IZIN yang masih akan
     * datang jatuh pada tanggal yang disebut baris ini menurut aturan izin sekarang (ruling
     * W5-FA, I1 tinjauan akhir W5). Baris yang masih tersimpan untuk hari buka lama, sesudah
     * aturannya dikoreksi, tidak dijanjikan untuk hari buka baru: penjadwal baru
     * menyelaraskannya pada hari buka lama.
     *
     * Tautan sistem resmi lewat PermitService::tautanPeriksa(), penentu berbasis filled() yang
     * juga dipakai PermitService::pemesananDariJendela() (ruling W5-FF, M3): alamat kosong atau
     * berisi spasi saja tidak pernah menjadi tautan.
     *
     * Kalimat "menautkan" ($resmi) dipilih SESUDAH $tautanResmi diketahui, lewat
     * PermitService::kalimatTautan() (ruling W5-G1, R-007): klaim itu hanya benar ketika tautan
     * yang sama persis dengan baris 'tautan' di bawah sungguh tampil.
     *
     * @return list<array<string, mixed>>
     */
    private function izin(TripPlan $trip, ?PermitRequirement $aturan, string $zona): array
    {
        $jendela = $aturan === null || PreparationService::izinDiurus($trip) ? null : $this->tenggatIzin->untuk($trip, $aturan);

        if ($jendela === null) {
            return [];
        }

        $tautanResmi = PermitService::tautanPeriksa($aturan);
        $resmi = 'Izin dipesan di sistem resmi '.$aturan->authority.'. '.PermitService::kalimatTautan($tautanResmi !== null);
        $ditutup = $jendela['keadaan'] === PermitService::BOOKING_DITUTUP;
        $hari = $jendela['tanggal'] === null ? null : CarbonImmutable::parse($jendela['tanggal']->toDateString(), $zona);
        $surel = $hari === null ? null : $trip->reminders->first(fn (TripReminder $p) => $p->jenis === TripReminderScheduler::JENIS_IZIN
            && $p->dijadwalkan_untuk->copy()->setTimezone($zona)->toDateString() === $hari->toDateString());

        return [[
            'urutan' => $hari?->getTimestamp() ?? PHP_INT_MAX,
            'kapan' => $hari === null ? null : self::kapan($hari, $zona),
            'judul' => $jendela['hari'] === null ? $jendela['judul'] : $jendela['judul'].' (H-'.$jendela['hari'].')',
            'keterangan' => match (true) {
                $ditutup => 'Butir izin di persiapan belum dikonfirmasi. Bila izin belum dipesan, tanyakan langsung ke '.$aturan->authority.'.',
                $jendela['catatan'] !== null => $jendela['catatan'].' '.$resmi,
                $surel !== null => $resmi.' Pengingat surel dikirim pukul '.$surel->dijadwalkan_untuk->copy()->setTimezone($zona)->format('H.i').' hari itu.',
                default => $resmi,
            },
            'tautan' => $tautanResmi === null ? null : ['label' => 'Buka sistem resmi', 'href' => $tautanResmi['href']],
            'peringatan' => $ditutup,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prakiraan(TripPlan $trip, string $zona): array
    {
        $terbit = PreparationService::terbitPrakiraan($trip);

        if ($terbit === null) {
            return [];
        }

        $hari = CarbonImmutable::parse($terbit->toDateString(), $zona);

        return [[
            'urutan' => $hari->getTimestamp(),
            'kapan' => self::kapan($hari, $zona),
            'judul' => 'Prakiraan BMKG untuk '.$trip->planned_date->translatedFormat('j M').' mulai terbit',
            'keterangan' => sprintf(
                'BMKG menerbitkan prakiraan untuk %d hari ke depan. Sebelum itu belum ada prakiraan untuk tanggal Anda, dan Memount tidak menebaknya.',
                (int) config('hiking.conditions.weather_forecast_days'),
            ),
            'tautan' => null,
            'peringatan' => false,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pengingat(TripPlan $trip, string $zona): array
    {
        return $trip->reminders
            ->filter(fn (TripReminder $p) => $p->jenis !== TripReminderScheduler::JENIS_IZIN)
            ->map(function (TripReminder $p) use ($zona): array {
                $saat = CarbonImmutable::instance($p->dijadwalkan_untuk)->setTimezone($zona);

                return [
                    'urutan' => $saat->getTimestamp(),
                    'kapan' => self::kapan($saat, $zona),
                    'judul' => 'Pengingat surel pukul '.$saat->format('H.i'),
                    'keterangan' => TripReminderScheduler::label($p->jenis).'.',
                    'tautan' => null,
                    'peringatan' => false,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Tanggal untuk penanda kalender (tanggal dan bulan; tahun bila bukan tahun berjalan) dan
     * kalimat lengkapnya untuk pembaca layar.
     *
     * @return array{tanggal: string, bulan: string, tahun: string|null, lengkap: string}
     */
    private static function kapan(CarbonInterface $hari, string $zona): array
    {
        $setempat = CarbonImmutable::instance($hari)->setTimezone($zona);

        return [
            'tanggal' => $setempat->format('j'),
            'bulan' => $setempat->translatedFormat('M'),
            'tahun' => $setempat->year === CarbonImmutable::now($zona)->year ? null : $setempat->format('Y'),
            'lengkap' => $setempat->translatedFormat('l, j F Y'),
        ];
    }
}
