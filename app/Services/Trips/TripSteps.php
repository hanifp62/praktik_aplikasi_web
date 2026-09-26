<?php

namespace App\Services\Trips;

use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Support\Collection;

/**
 * Rencana sebagai langkah berurutan: mana yang sudah selesai dan apa langkah
 * berikutnya (features/04, "Rencana Perjalanan").
 *
 * Langkah adalah pemandu, bukan gerbang: pendaki tetap boleh memulai hike mode sebelum
 * persiapannya lengkap, sama seperti sebelumnya. Yang ditambahkan hanya keterbacaan.
 * Tautan hanya ada pada langkah perencanaan (persiapan, kesiapan); aksi yang mengubah
 * status tetap di kartu Tindakan halaman trip, supaya satu aksi punya satu tempat.
 *
 * Langkah "Cek kesiapan" hanya dihitung selesai bila pre-departure check dikonfirmasi
 * DAN vonisnya masih mewakili keadaan. Tanda selesai di samping vonis basi akan terbaca
 * sebagai "sudah aman", dan itu persis yang dilarang R-014.
 */
class TripSteps
{
    public const SELESAI = 'selesai';

    public const BERIKUTNYA = 'berikutnya';

    public const BELUM = 'belum';

    public function __construct(private readonly TripLifecycle $lifecycle) {}

    /**
     * Membutuhkan relasi trail.mountain, preparationItems, latestReadinessCheck, dan
     * history sudah dimuat.
     *
     * @return array<int, array{keadaan: string, kunci: string, judul: string, keterangan: string, catatan: string|null, peringatan: bool, tautan: array{href: string, teks: string}|null}>
     */
    public function for(TripPlan $trip): array
    {
        $persiapan = self::hitungPersiapan($trip->preparationItems);
        $kesiapan = $this->kesiapan($trip);
        $adaRiwayat = $trip->history !== null;

        $langkah = [
            [
                'kunci' => 'jalur',
                'judul' => 'Pilih jalur',
                'selesai' => true,
                'keterangan' => $trip->trail->name.' - '.$trip->trail->mountain->name,
                'catatan' => null,
                'peringatan' => false,
                'tautan' => null,
            ],
            [
                'kunci' => 'rencana',
                'judul' => 'Buat rencana',
                'selesai' => true,
                'keterangan' => self::teksRencana($trip),
                'catatan' => null,
                'peringatan' => false,
                // Mengubah isi rencana adalah langkah perencanaan, bukan perubahan status,
                // jadi tautannya tinggal di sini selama rencana masih dapat diubah.
                'tautan' => $this->lifecycle->editRefusal($trip) === null
                    ? ['href' => route('trips.edit', $trip), 'teks' => 'Ubah rencana']
                    : null,
            ],
            [
                'kunci' => 'persiapan',
                'judul' => 'Persiapan',
                'selesai' => $persiapan['berlaku'] > 0 && $persiapan['dikonfirmasi'] === $persiapan['berlaku'],
                'keterangan' => self::teksPersiapan($persiapan),
                'catatan' => null,
                'peringatan' => false,
                'tautan' => ['href' => route('trips.preparation', $trip), 'teks' => 'Buka daftar persiapan'],
            ],
            [
                'kunci' => 'kesiapan',
                'judul' => 'Cek kesiapan',
                'selesai' => $kesiapan['selesai'],
                'keterangan' => $kesiapan['keterangan'],
                'catatan' => $kesiapan['catatan'],
                'peringatan' => $kesiapan['peringatan'],
                'tautan' => ['href' => route('trips.readiness', $trip), 'teks' => 'Cek kesiapan'],
            ],
            [
                'kunci' => 'mendaki',
                'judul' => 'Hike mode',
                'selesai' => $trip->status === TripStatus::COMPLETED,
                'keterangan' => match ($trip->status) {
                    TripStatus::IN_PROGRESS, TripStatus::COMPLETED, TripStatus::CANCELLED => $trip->status->label(),
                    default => 'Belum dimulai',
                },
                'catatan' => null,
                'peringatan' => false,
                'tautan' => null,
            ],
            [
                'kunci' => 'selesai',
                'judul' => 'Selesaikan trip',
                'selesai' => $adaRiwayat,
                'keterangan' => $adaRiwayat
                    ? 'Tersimpan di riwayat pendakian.'
                    : 'Tandai trip selesai untuk menyimpannya ke riwayat dan melaporkan kondisi jalur.',
                'catatan' => null,
                'peringatan' => false,
                'tautan' => null,
            ],
        ];

        // Hanya trip yang masih berjalan yang punya langkah berikutnya. Trip yang
        // dibatalkan atau selesai tidak menunjuk ke mana pun.
        $sudahDitunjuk = ! $trip->status->isActive();
        $hasil = [];

        foreach ($langkah as $l) {
            $keadaan = self::BELUM;

            if ($l['selesai']) {
                $keadaan = self::SELESAI;
            } elseif (! $sudahDitunjuk) {
                $keadaan = self::BERIKUTNYA;
                $sudahDitunjuk = true;
            }

            unset($l['selesai']);
            $hasil[] = ['keadaan' => $keadaan] + $l;
        }

        return $hasil;
    }

    /**
     * Ringkasan rencana: rentang tanggal, tipe, dan peserta. Yang belum disebut pendaki
     * tidak ditulis sama sekali, bukan diisi tebakan (R-007).
     */
    public static function teksRencana(TripPlan $trip): string
    {
        $tanggal = $trip->planned_date->translatedFormat('d M Y');

        if ($trip->end_date !== null && ! $trip->end_date->isSameDay($trip->planned_date)) {
            $tanggal .= ' - '.$trip->end_date->translatedFormat('d M Y');
        }

        return implode(' · ', array_filter([
            $tanggal,
            $trip->trip_type->label(),
            $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
        ]));
    }

    /**
     * Hitungan butir persiapan dari koleksi yang sudah dimuat. "Tidak berlaku" tidak
     * ikut dihitung, sama seperti TripPlan::preparationCompletionPercent().
     *
     * @param  Collection<int, TripPreparationItem>  $items
     * @return array{dikonfirmasi: int, berlaku: int}
     */
    public static function hitungPersiapan(Collection $items): array
    {
        $berlaku = $items->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        return [
            'dikonfirmasi' => $berlaku->where('status', PreparationStatus::CONFIRMED)->count(),
            'berlaku' => $berlaku->count(),
        ];
    }

    /**
     * Kemajuan sebagai hitungan, bukan persentase: "3 dari 8" terbaca sebagai sisa
     * pekerjaan, sedangkan "38%" mudah terbaca sebagai nilai kesiapan (R-005).
     *
     * @param  array{dikonfirmasi: int, berlaku: int}  $persiapan
     */
    public static function teksPersiapan(array $persiapan): string
    {
        if ($persiapan['berlaku'] === 0) {
            return 'Belum ada item persiapan.';
        }

        return sprintf('%d dari %d item dikonfirmasi', $persiapan['dikonfirmasi'], $persiapan['berlaku']);
    }

    /**
     * Vonis tersimpan tidak pernah disajikan sebagai keadaan sekarang ketika status
     * resmi jalurnya sudah berubah sejak penilaian. Menyebut label lamanya di samping
     * pengakuan itu memberi pendaki dua jawaban yang bertentangan, jadi labelnya
     * diganti, bukan didampingi.
     *
     * @return array{selesai: bool, keterangan: string, catatan: string|null, peringatan: bool}
     */
    private function kesiapan(TripPlan $trip): array
    {
        if ($trip->trailIsWithdrawn()) {
            return [
                'selesai' => false,
                'keterangan' => 'Jalur ditarik dari katalog',
                'catatan' => 'Keterangan jalur ini tidak lagi diperbarui di sini. Tanyakan keadaannya langsung kepada pengelola kawasan.',
                'peringatan' => true,
            ];
        }

        if ($trip->readinessIsStale()) {
            return [
                'selesai' => false,
                'keterangan' => 'Status jalur berubah, perlu dinilai ulang',
                'catatan' => 'Penilaian terakhir dibuat ketika status resminya masih berbeda.',
                'peringatan' => true,
            ];
        }

        $terakhir = $trip->latestReadinessCheck;
        $dikonfirmasi = (bool) $terakhir?->pre_departure_confirmed;

        return [
            'selesai' => $dikonfirmasi,
            'keterangan' => $terakhir?->computed_state->label() ?? 'Belum dinilai',
            'catatan' => $dikonfirmasi ? 'Pre-departure check sudah dikonfirmasi.' : null,
            'peringatan' => false,
        ];
    }
}
