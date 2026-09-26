<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Notifications\TripReminderNotification;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pengingat perjalanan H-3 dan H-1 (FR-24, features/04).
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

    /**
     * Instan UTC tiap jenis pengingat: pukul local_hour di zona gunung, sekian hari
     * sebelum tanggal mulai.
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

        return $hasil;
    }

    public function dapatDiatur(TripPlan $trip): bool
    {
        return in_array($trip->status, self::STATUS_LAYAK, true);
    }

    /**
     * Pengingat yang masih menunggu mengikuti rencana. Yang sudah terkirim atau
     * dilewati untuk jadwal yang sama dibiarkan, sehingga mematikan lalu menyalakan
     * kembali tidak pernah mengirim ulang. Jadwal yang bergeser (tanggal diubah)
     * menyebut tanggal lain, jadi pengingatnya dijadwalkan ulang dari awal.
     */
    public function sinkronkan(TripPlan $trip, ?CarbonInterface $sekarang = null): void
    {
        $sekarang ??= Carbon::now();
        $trip->loadMissing('trail.mountain');
        $trip->load('reminders');

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
     * Mengirim pengingat yang sudah jatuh tempo, satu surel per trip per jalan.
     *
     * Bila beberapa pengingat satu trip jatuh tempo bersamaan (scheduler sempat mati),
     * hanya yang paling dekat dengan keberangkatan yang dikirim; sisanya dicatat
     * dilewati. Pada hari keberangkatan tidak ada pengingat yang dikirim lagi.
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
                ->with('tripPlan.trail.mountain', 'tripPlan.user')
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
     * keadaannya. Membutuhkan relasi reminders dan trail.mountain sudah dimuat.
     *
     * @return array{aktif: bool, dapatDiatur: bool, jadwal: string, daftar: array<int, array{jenis: string, label: string, waktu: string, keadaan: string, keadaanTeks: string}>, catatan: string|null}
     */
    public function untukHalaman(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $ada = $trip->reminders->sortBy('dijadwalkan_untuk')->values();

        $daftar = $ada->map(fn (TripReminder $p) => [
            'jenis' => $p->jenis,
            'label' => self::label($p->jenis),
            'waktu' => (string) Timezone::display($p->dijadwalkan_untuk, $zona, self::FORMAT_WAKTU),
            'keadaan' => $p->keadaan(),
            'keadaanTeks' => match ($p->keadaan()) {
                TripReminder::TERKIRIM => 'Terkirim '.Timezone::display($p->sent_at, $zona, self::FORMAT_WAKTU),
                TripReminder::DILEWATI => 'Tidak dikirim: waktunya terlewat',
                default => 'Menunggu',
            },
        ])->all();

        $terlewat = $trip->reminders_enabled && $this->dapatDiatur($trip)
            ? array_values(array_diff(array_keys($this->jadwal($trip)), $ada->pluck('jenis')->all()))
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
            'Pengingat dikirim %s sebelum tanggal mulai, pukul %02d:00 waktu setempat gunungnya (WIB, WITA, atau WIT).',
            implode(' dan ', $hari),
            (int) config('hiking.reminders.local_hour'),
        );
    }

    public static function label(string $jenis): string
    {
        $hari = (int) substr($jenis, 2);

        return $hari === 1 ? 'Sehari sebelum berangkat' : $hari.' hari sebelum berangkat';
    }

    /**
     * @param  Collection<int, TripReminder>  $pengingat  pengingat jatuh tempo milik satu trip
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil): bool
    {
        $trip = $pengingat->first()?->tripPlan;

        if ($trip === null) {
            return true;
        }

        $zona = Timezone::forTrail($trip->trail);
        $hariIni = CarbonImmutable::instance($sekarang)->setTimezone($zona)->toDateString();
        $keberangkatan = $trip->planned_date->toDateString();

        // Pemeriksaan ulang saat kirim, tidak hanya saat menjadwalkan: jalur mana pun
        // yang lupa menyelaraskan tetap tidak menghasilkan surel yang salah.
        $layak = $trip->reminders_enabled && $this->dapatDiatur($trip) && $hariIni < $keberangkatan;
        $terdekat = $pengingat->sortBy('dijadwalkan_untuk')->last();

        foreach ($pengingat as $satu) {
            if ((! $layak || $satu->isNot($terdekat)) && $this->lewati($satu, $sekarang)) {
                $hasil['dilewati']++;
            }
        }

        if (! $layak || ! $this->klaim($terdekat, $sekarang)) {
            return true;
        }

        $sisaHari = (int) CarbonImmutable::parse($hariIni)->diffInDays(CarbonImmutable::parse($keberangkatan));

        try {
            $trip->user->notify(new TripReminderNotification($trip, $sisaHari));
        } catch (Throwable $e) {
            TripReminder::query()->whereKey($terdekat->id)->update(['sent_at' => null]);
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
