<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Notifications\PengingatIzinDibuka;
use App\Notifications\TripReminderNotification;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pengingat perjalanan H-3 dan H-1 (FR-24, features/04), ditambah hari pemesanan izin dibuka
 * (spec v3 bagian 6.1, aksi Beranda "Ingatkan saat dibuka", GR-06) bila aturan izin jalurnya
 * mencatat kapan pemesanan dibuka. Ketiganya ikut satu sakelar pengingat trip.
 *
 * Dua tugas. sinkronkan() menyelaraskan baris trip_reminders dengan keadaan rencana,
 * dipanggil setiap kali rencana dibuat, diubah, dimulai, diselesaikan, dibatalkan,
 * atau sakelarnya diubah. kirimYangJatuhTempo() mengirim yang sudah tiba waktunya,
 * dipanggil perintah terjadwal trips:kirim-pengingat setiap jam.
 *
 * Jaminan tidak terkirim dua kali ada di klaim atomik: baris diubah menjadi terkirim
 * dengan UPDATE bersyarat "belum terkirim dan belum dilewati" sebelum surel dikirim,
 * sehingga dua proses yang berjalan bersamaan tidak pernah sama-sama memegang baris
 * yang sama. Gagal kirim melepas klaimnya supaya jalan berikutnya mencoba lagi.
 *
 * Surel dikirim sinkron, bukan lewat antrean: perintah terjadwal ini sudah proses
 * latar, dan tanpa pekerja antrean yang berjalan, surel yang diantrekan akan tercatat
 * terkirim padahal tidak pernah berangkat.
 */
class TripReminderScheduler
{
    /**
     * Rencana yang masih menunggu keberangkatan. Trip yang sudah dimulai, selesai,
     * atau dibatalkan tidak diingatkan lagi.
     *
     * @var array<int, TripStatus>
     */
    private const STATUS_LAYAK = [TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE];

    /** Trip per putaran pengiriman, supaya memori tidak tumbuh bersama antrean. */
    private const PER_PUTARAN = 100;

    private const FORMAT_WAKTU = 'l, d M Y H:i';

    /** Jenis pengingat hari pemesanan izin dibuka; muat di kolom jenis (8 karakter). */
    public const JENIS_IZIN = 'IZIN';

    public function __construct(private readonly PermitService $izin) {}

    /**
     * Instan UTC tiap jenis pengingat: pukul local_hour di zona gunung, sekian hari
     * sebelum tanggal mulai. Hari pemesanan izin dibuka ikut bila aturan izin jalurnya
     * mencatat kapan pemesanan dibuka dan butir izin di persiapan belum diurus (ruling W5-FA,
     * M4 tinjauan akhir W5); tanpa catatan itu tidak ada tanggal yang diingatkan, dan
     * tanggalnya tidak ditebak (R-007). Membutuhkan relasi trail dan preparationItems sudah
     * dimuat.
     *
     * @return array<string, CarbonImmutable> jenis => instan UTC
     */
    public function jadwal(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $jam = (int) config('hiking.reminders.local_hour');
        $hasil = [];

        foreach ($this->hariSebelum() as $hari) {
            $hasil[self::jenis($hari)] = CarbonImmutable::parse($trip->planned_date->toDateString(), $zona)
                ->subDays($hari)
                ->setTime($jam, 0)
                ->utc();
        }

        $buka = PreparationService::izinDiurus($trip) ? null : $this->bukaIzin($trip);

        if ($buka !== null) {
            $hasil[self::JENIS_IZIN] = $buka;
        }

        return $hasil;
    }

    /**
     * Instan UTC pengingat hari pemesanan izin dibuka, atau null bila aturan izin jalurnya
     * tidak mencatat kapan pemesanan dibuka. $aturan false berarti belum dicari dan null
     * berarti sudah dicari dan memang tidak ada, pola yang sama dengan
     * PermitService::bookingWindowFor(), supaya pemanggil yang sudah memegang aturannya
     * (Beranda) tidak mengueri ulang.
     */
    public function bukaIzin(TripPlan $trip, PermitRequirement|false|null $aturan = false): ?CarbonImmutable
    {
        $aturan = $aturan === false ? $this->izin->requirementFor($trip->trail) : $aturan;
        $hari = $aturan?->booking_opens_days_before;

        if ($hari === null) {
            return null;
        }

        return CarbonImmutable::parse($trip->planned_date->toDateString(), Timezone::forTrail($trip->trail))
            ->subDays($hari)
            ->setTime((int) config('hiking.reminders.local_hour'), 0)
            ->utc();
    }

    public function dapatDiatur(TripPlan $trip): bool
    {
        return in_array($trip->status, self::STATUS_LAYAK, true);
    }

    /**
     * Pengingat yang masih menunggu mengikuti rencana. Yang sudah terkirim atau
     * dilewati untuk jadwal yang sama dibiarkan, sehingga mematikan lalu menyalakan
     * kembali tidak pernah mengirim ulang. Jadwal yang bergeser (tanggal diubah, atau aturan
     * izin dikoreksi) menyebut tanggal lain, jadi pengingatnya dijadwalkan ulang dari awal.
     *
     * Butir persiapan dibaca ulang dari basis data, bukan dari relasi yang mungkin sudah
     * dimuat sebelum butir izin diubah: jadwal() memutuskan ada tidaknya baris IZIN dari situ.
     */
    public function sinkronkan(TripPlan $trip, ?CarbonInterface $sekarang = null): void
    {
        $sekarang ??= Carbon::now();
        $trip->loadMissing('trail.mountain');
        $trip->load('reminders', 'preparationItems');

        $aktif = $trip->reminders_enabled && $this->dapatDiatur($trip);
        $diinginkan = $this->jadwal($trip);
        $ada = $trip->reminders->keyBy('jenis');

        DB::transaction(function () use ($trip, $sekarang, $aktif, $diinginkan, $ada): void {
            foreach ($ada as $jenis => $pengingat) {
                if ($pengingat->keadaan() === TripReminder::MENUNGGU && (! $aktif || ! isset($diinginkan[$jenis]))) {
                    $pengingat->delete();
                }
            }

            if (! $aktif) {
                return;
            }

            foreach ($diinginkan as $jenis => $waktu) {
                $pengingat = $ada->get($jenis);
                $masihAkanDatang = $waktu->greaterThan($sekarang);

                if ($pengingat === null) {
                    if ($masihAkanDatang) {
                        TripReminder::createOrFirst(
                            ['trip_plan_id' => $trip->id, 'jenis' => $jenis],
                            ['dijadwalkan_untuk' => $waktu],
                        );
                    }

                    continue;
                }

                if ($pengingat->dijadwalkan_untuk->equalTo($waktu)) {
                    continue;
                }

                if ($masihAkanDatang) {
                    $pengingat->update(['dijadwalkan_untuk' => $waktu, 'sent_at' => null, 'skipped_at' => null]);
                } else {
                    $pengingat->delete();
                }
            }
        });

        $trip->unsetRelation('reminders');
    }

    /**
     * Mengirim pengingat yang sudah jatuh tempo: paling banyak satu surel H-n per trip per
     * jalan, ditambah surel hari pemesanan izin dibuka yang diputuskan terpisah (ruling
     * W5-FA). Keduanya terkirim bila jatuh tempo pada pagi yang sama, karena tujuannya berbeda.
     *
     * Bila beberapa pengingat H-n satu trip jatuh tempo bersamaan (scheduler sempat mati),
     * hanya yang paling dekat dengan keberangkatan yang dikirim; sisanya dicatat
     * dilewati. Pada hari keberangkatan tidak ada pengingat H-n yang dikirim lagi.
     *
     * @return array{terkirim: int, dilewati: int, gagal: int}
     */
    public function kirimYangJatuhTempo(CarbonInterface $sekarang): array
    {
        $hasil = ['terkirim' => 0, 'dilewati' => 0, 'gagal' => 0];
        $tripGagal = [];

        do {
            // Baris yang sudah diproses keluar dari himpunan jatuh tempo, jadi setiap
            // putaran cukup mengambil trip pertama yang tersisa. Trip yang gagal pada
            // jalan ini dikecualikan supaya putarannya berhenti.
            $tripIds = TripReminder::query()
                ->menunggu()
                ->where('dijadwalkan_untuk', '<=', $sekarang)
                ->whereNotIn('trip_plan_id', $tripGagal)
                ->distinct()
                ->orderBy('trip_plan_id')
                ->limit(self::PER_PUTARAN)
                ->pluck('trip_plan_id');

            $perTrip = TripReminder::query()
                ->menunggu()
                ->where('dijadwalkan_untuk', '<=', $sekarang)
                ->whereIn('trip_plan_id', $tripIds)
                ->with('tripPlan.trail.mountain', 'tripPlan.user', 'tripPlan.preparationItems')
                ->get()
                ->groupBy('trip_plan_id');

            foreach ($perTrip as $tripId => $pengingat) {
                if (! $this->prosesTrip($pengingat, $sekarang, $hasil)) {
                    $tripGagal[] = $tripId;
                }
            }
        } while ($tripIds->isNotEmpty());

        return $hasil;
    }

    /**
     * Isi untuk halaman trip: sakelar, jadwal yang berlaku, dan tiap pengingat beserta
     * keadaannya. Membutuhkan relasi reminders, trail.mountain, dan preparationItems sudah
     * dimuat.
     *
     * Baris IZIN yang dilewati selagi izinnya sudah diurus tidak pernah disebut terlewat (ruling
     * W5-FA, M4 tinjauan akhir W5): sebabnya izin yang sudah diurus, bukan jam yang lewat.
     *
     * @return array{aktif: bool, dapatDiatur: bool, jadwal: string, daftar: array<int, array{jenis: string, label: string, waktu: string, keadaan: string, keadaanTeks: string}>, catatan: string|null}
     */
    public function untukHalaman(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $ada = $trip->reminders->sortBy('dijadwalkan_untuk')->values();
        $izinDiurus = PreparationService::izinDiurus($trip);

        $daftar = $ada->map(fn (TripReminder $p) => [
            'jenis' => $p->jenis,
            'label' => self::label($p->jenis),
            'waktu' => (string) Timezone::display($p->dijadwalkan_untuk, $zona, self::FORMAT_WAKTU),
            'keadaan' => $p->keadaan(),
            'keadaanTeks' => match (true) {
                $p->keadaan() === TripReminder::TERKIRIM => 'Terkirim '.Timezone::display($p->sent_at, $zona, self::FORMAT_WAKTU),
                $p->keadaan() === TripReminder::DILEWATI && $p->jenis === self::JENIS_IZIN && $izinDiurus => 'Tidak dikirim: izin sudah diurus',
                $p->keadaan() === TripReminder::DILEWATI => 'Tidak dikirim: waktunya terlewat',
                default => 'Menunggu',
            },
        ])->all();

        // Hari pemesanan izin yang sudah lewat tidak disebut terlewat: pemesanannya sudah
        // dibuka, dan yang perlu dilakukan sekarang memesan, bukan menunggu surel.
        $terlewat = $trip->reminders_enabled && $this->dapatDiatur($trip)
            ? array_values(array_diff(array_keys($this->jadwal($trip)), $ada->pluck('jenis')->all(), [self::JENIS_IZIN]))
            : [];

        return [
            'aktif' => (bool) $trip->reminders_enabled,
            'dapatDiatur' => $this->dapatDiatur($trip),
            'jadwal' => $this->keteranganJadwal(),
            'daftar' => $daftar,
            'catatan' => $terlewat === [] ? null : sprintf(
                'Pengingat %s tidak dijadwalkan karena waktunya sudah lewat.',
                implode(' dan ', array_map(fn (string $jenis) => mb_strtolower(self::label($jenis)), $terlewat)),
            ),
        ];
    }

    /**
     * Kalimat jadwal dari config, supaya formulir tidak menyebut jam yang berbeda dari
     * yang dijalankan.
     */
    public function keteranganJadwal(): string
    {
        $hari = array_map(fn (int $h) => $h === 1 ? 'sehari' : $h.' hari', $this->hariSebelum());

        return sprintf(
            'Pengingat dikirim %s sebelum tanggal mulai, pukul %02d:00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.',
            implode(' dan ', $hari),
            (int) config('hiking.reminders.local_hour'),
        );
    }

    public static function label(string $jenis): string
    {
        if ($jenis === self::JENIS_IZIN) {
            return 'Saat pemesanan izin dibuka';
        }

        $hari = (int) substr($jenis, 2);

        return $hari === 1 ? 'Sehari sebelum berangkat' : $hari.' hari sebelum berangkat';
    }

    /**
     * Pemeriksaan ulang saat kirim, tidak hanya saat menjadwalkan: jalur mana pun yang lupa
     * menyelaraskan tetap tidak menghasilkan surel yang salah. Rantai H-n diputuskan lebih dulu
     * dan baris IZIN sesudahnya, masing-masing dengan syaratnya sendiri (ruling W5-FA), supaya
     * gerbang izin tidak pernah ikut menahan surel H-n, dan supaya penyelarasan ulang baris IZIN
     * tidak menyentuh baris H-n yang belum diputuskan.
     *
     * @param  Collection<int, TripReminder>  $pengingat  pengingat jatuh tempo milik satu trip
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila salah satu pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil): bool
    {
        $trip = $pengingat->first()?->tripPlan;

        if ($trip === null) {
            return true;
        }

        [$izin, $rantai] = $pengingat->partition(fn (TripReminder $p) => $p->jenis === self::JENIS_IZIN);

        $berhasil = $this->prosesRantai($trip, $rantai, $sekarang, $hasil);

        foreach ($izin as $satu) {
            $berhasil = $this->prosesIzin($trip, $satu, $sekarang, $hasil) && $berhasil;
        }

        return $berhasil;
    }

    /**
     * Rantai H-n: satu surel per jalan, yang paling dekat dengan keberangkatan; sisanya dicatat
     * dilewati. Tidak ada surel pada hari keberangkatan.
     *
     * @param  Collection<int, TripReminder>  $rantai  pengingat H-n jatuh tempo milik $trip
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila pengiriman gagal
     */
    private function prosesRantai(TripPlan $trip, Collection $rantai, CarbonInterface $sekarang, array &$hasil): bool
    {
        if ($rantai->isEmpty()) {
            return true;
        }

        $zona = Timezone::forTrail($trip->trail);
        $hariIni = CarbonImmutable::instance($sekarang)->setTimezone($zona)->toDateString();
        $keberangkatan = $trip->planned_date->toDateString();

        $terdekat = $rantai->sortBy('dijadwalkan_untuk')->last();
        $layak = $trip->reminders_enabled && $this->dapatDiatur($trip) && $hariIni < $keberangkatan;

        foreach ($rantai as $satu) {
            if ((! $layak || $satu->isNot($terdekat)) && $this->lewati($satu, $sekarang)) {
                $hasil['dilewati']++;
            }
        }

        if (! $layak || ! $this->klaim($terdekat, $sekarang)) {
            return true;
        }

        $sisaHari = (int) CarbonImmutable::parse($hariIni)->diffInDays(CarbonImmutable::parse($keberangkatan));

        return $this->kirim($trip, $terdekat, new TripReminderNotification($trip, $sisaHari), $hasil);
    }

    /**
     * Surel hari pemesanan izin dibuka berbunyi "dibuka hari ini", jadi hanya dikirim bila
     * sakelar menyala, rencana masih dapat diatur, izin belum diurus, hari buka menurut aturan
     * izin SEKARANG sama dengan jadwal baris ini, dan hari ini di zona gunungnya memang hari buka
     * itu (ruling W5-FA). Penjadwal yang terlambat sesudah hari buka, atau aturan yang sudah tidak
     * mencatat hari buka, mencatat barisnya dilewati.
     *
     * Aturan yang dikoreksi sesudah barisnya dijadwalkan dan hari buka barunya masih akan datang
     * tidak melewatkan apa pun: rencananya diselaraskan ulang, dan baris itu berpindah ke hari
     * buka baru.
     *
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila pengiriman gagal
     */
    private function prosesIzin(TripPlan $trip, TripReminder $pengingat, CarbonInterface $sekarang, array &$hasil): bool
    {
        $aturan = $this->izin->requirementFor($trip->trail);
        $buka = $this->bukaIzin($trip, $aturan);
        $gerbang = $trip->reminders_enabled && $this->dapatDiatur($trip) && ! PreparationService::izinDiurus($trip);

        if ($gerbang && $buka !== null && ! $buka->equalTo($pengingat->dijadwalkan_untuk) && $buka->greaterThan($sekarang)) {
            $this->sinkronkan($trip, $sekarang);

            return true;
        }

        $zona = Timezone::forTrail($trip->trail);
        $layak = $gerbang
            && $buka !== null
            && $buka->equalTo($pengingat->dijadwalkan_untuk)
            && CarbonImmutable::instance($sekarang)->setTimezone($zona)->toDateString() === $buka->setTimezone($zona)->toDateString();

        if (! $layak) {
            if ($this->lewati($pengingat, $sekarang)) {
                $hasil['dilewati']++;
            }

            return true;
        }

        if (! $this->klaim($pengingat, $sekarang)) {
            return true;
        }

        return $this->kirim($trip, $pengingat, new PengingatIzinDibuka($trip, $aturan), $hasil);
    }

    /**
     * Mengirim surel untuk baris yang sudah diklaim. Gagal kirim melepas klaimnya supaya jalan
     * berikutnya mencoba lagi.
     *
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila pengiriman gagal
     */
    private function kirim(TripPlan $trip, TripReminder $pengingat, Notification $surel, array &$hasil): bool
    {
        try {
            $trip->user->notify($surel);
        } catch (Throwable $e) {
            TripReminder::query()->whereKey($pengingat->id)->update(['sent_at' => null]);
            report($e);
            $hasil['gagal']++;

            return false;
        }

        $hasil['terkirim']++;

        return true;
    }

    private function klaim(TripReminder $pengingat, CarbonInterface $sekarang): bool
    {
        return TripReminder::query()->whereKey($pengingat->id)->menunggu()->update(['sent_at' => $sekarang]) === 1;
    }

    private function lewati(TripReminder $pengingat, CarbonInterface $sekarang): bool
    {
        return TripReminder::query()->whereKey($pengingat->id)->menunggu()->update(['skipped_at' => $sekarang]) === 1;
    }

    /**
     * @return array<int, int> hari sebelum keberangkatan, dari yang terjauh
     */
    private function hariSebelum(): array
    {
        $hari = array_filter(
            array_map(intval(...), (array) config('hiking.reminders.days_before')),
            fn (int $h) => $h > 0,
        );

        rsort($hari);

        return array_values(array_unique($hari));
    }

    private static function jenis(int $hari): string
    {
        return 'H-'.$hari;
    }
}
