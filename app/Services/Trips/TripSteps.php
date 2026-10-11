<?php

namespace App\Services\Trips;

use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use App\Support\Timezone;
use Illuminate\Support\Collection;

/**
 * Rencana sebagai empat langkah prototype 08 sampai 10 (spec v3 bagian 6.9): Jalur, Rencana,
 * Persiapan, Cek. Mana yang sudah selesai dan apa langkah berikutnya (features/04).
 *
 * Langkah adalah pemandu, bukan gerbang: pendaki tetap boleh memulai pendakian sebelum
 * persiapannya lengkap. Memulai, menyelesaikan, dan membatalkan trip adalah aksi di kartu
 * Tindakan ruang kerja, bukan langkah, supaya satu aksi punya satu tempat.
 *
 * Persiapan yang hanya menyisakan butir menunggu BMKG bukan langkah berikutnya: butir itu
 * belum dapat dikerjakan, jadi pemandu menunjuk Cek (ruling W4-FB, I2 tinjauan akhir W4).
 *
 * Langkah "Cek" selesai bila tinjauan pra-berangkat tercatat (TripPlan::tinjauanTercatat(),
 * predikat yang sama dengan Cek kesiapan) DAN vonisnya masih mewakili keadaan. Tanda selesai
 * di samping vonis basi akan terbaca sebagai "sudah aman", dan itu persis yang dilarang R-014.
 */
class TripSteps
{
    public const SELESAI = 'selesai';

    public const BERIKUTNYA = 'berikutnya';

    public const BELUM = 'belum';

    /**
     * Membutuhkan relasi trail.mountain, preparationItems, latestReadinessCheck, dan
     * latestConfirmedReadinessCheck sudah dimuat. Pemanggil mengoper status resmi sekarang yang
     * sudah dimuatnya, supaya pemeriksaan vonis basi tidak menjadi kueri tambahan.
     *
     * @return array<int, array{keadaan: string, kunci: string, judul: string, keterangan: string, catatan: string|null, peringatan: bool, tautan: array{href: string, teks: string}|null}>
     */
    public function for(TripPlan $trip, ?OfficialStatusValue $statusSekarang = null): array
    {
        $persiapan = self::hitungPersiapan($trip->preparationItems);
        $menunggu = self::semuaMenungguBmkg($trip, $persiapan);
        $kesiapan = $this->kesiapan($trip, $statusSekarang);

        $langkah = [
            [
                'kunci' => 'jalur',
                'judul' => 'Jalur',
                'selesai' => true,
                'menunggu' => false,
                // Ditulis sama dengan kepala trip di halaman yang sama (tinjauan Tugas 1 M4).
                'keterangan' => $trip->trail->name.', '.$trip->trail->mountain->name,
                'catatan' => null,
                'peringatan' => false,
                // Jalur draf atau arsip tidak ditautkan: rinciannya bukan rincian jalur terbit.
                'tautan' => $trip->trail->terbit() ? ['href' => route('trails.show', $trip->trail), 'teks' => 'Lihat jalur'] : null,
            ],
            [
                'kunci' => 'rencana',
                'judul' => 'Rencana',
                'selesai' => true,
                'menunggu' => false,
                'keterangan' => self::teksRencana($trip),
                'catatan' => null,
                'peringatan' => false,
                // "Ubah rencana" tinggal di kepala trip (KepalaTrip), satu tempat untuk satu
                // aksi, selama rencana masih dapat diubah.
                'tautan' => null,
            ],
            [
                'kunci' => 'persiapan',
                'judul' => 'Persiapan',
                'selesai' => $persiapan['berlaku'] > 0 && $persiapan['dikonfirmasi'] === $persiapan['berlaku'],
                'menunggu' => $menunggu !== null,
                'keterangan' => $menunggu ?? self::teksPersiapan($persiapan),
                'catatan' => null,
                'peringatan' => false,
                'tautan' => ['href' => route('trips.preparation', $trip), 'teks' => 'Buka daftar persiapan'],
            ],
            [
                'kunci' => 'kesiapan',
                'judul' => 'Cek',
                'selesai' => $kesiapan['selesai'],
                'menunggu' => false,
                'keterangan' => $kesiapan['keterangan'],
                'catatan' => $kesiapan['catatan'],
                'peringatan' => $kesiapan['peringatan'],
                'tautan' => ['href' => route('trips.readiness', $trip), 'teks' => 'Cek kesiapan'],
            ],
        ];

        // Hanya rencana yang masih di depan yang punya langkah berikutnya. Trip yang berlangsung,
        // selesai, atau dibatalkan tidak menunjuk ke langkah perencanaan mana pun, dan rencana
        // yang tanggalnya sudah lewat hanya diajak mengubah rencananya (aksi(), ruling W4-1c dan
        // W4-FK N2). Langkah yang hanya menunggu BMKG dilewati sebagai langkah berikutnya, tidak
        // ditandai selesai.
        $sudahDitunjuk = ! $trip->masihDirencanakan();
        $hasil = [];

        foreach ($langkah as $l) {
            $keadaan = self::BELUM;

            if ($l['selesai']) {
                $keadaan = self::SELESAI;
            } elseif (! $sudahDitunjuk && ! $l['menunggu']) {
                $keadaan = self::BERIKUTNYA;
                $sudahDitunjuk = true;
            }

            unset($l['selesai'], $l['menunggu']);
            $hasil[] = ['keadaan' => $keadaan] + $l;
        }

        return $hasil;
    }

    /**
     * Aksi utama berikutnya untuk kartu trip di indeks (spec v3 bagian 6.9): langkah pertama
     * yang belum selesai, atau keadaan pendakiannya. Trip selesai menautkan ringkasannya bila
     * riwayatnya ada (relasi history harus dimuat); trip batal tidak punya aksi berikutnya.
     * Rencana yang tanggalnya sudah lewat tanpa pernah dimulai diajak mengubah rencananya,
     * bukan melanjutkan persiapan untuk tanggal yang sudah lewat (ruling W4-1c).
     *
     * @param  array<int, array{keadaan: string, kunci: string}>  $langkah  hasil for()
     * @return array{label: string, href: string}|null
     */
    public static function aksi(TripPlan $trip, array $langkah): ?array
    {
        if ($trip->status === TripStatus::IN_PROGRESS) {
            return ['label' => 'Buka mode lapangan', 'href' => route('trips.hike', $trip)];
        }

        if (! $trip->status->isActive()) {
            return $trip->history !== null ? ['label' => 'Lihat ringkasan', 'href' => route('history.summary', $trip)] : null;
        }

        if ($trip->tanggalRencanaLewat()) {
            return ['label' => 'Ubah rencana', 'href' => route('trips.edit', $trip)];
        }

        return match (collect($langkah)->firstWhere('keadaan', self::BERIKUTNYA)['kunci'] ?? null) {
            'persiapan' => ['label' => 'Lanjutkan persiapan', 'href' => route('trips.preparation', $trip)],
            'kesiapan' => ['label' => 'Cek kesiapan', 'href' => route('trips.readiness', $trip)],
            default => ['label' => 'Buka ruang kerja', 'href' => route('trips.show', $trip)],
        };
    }

    /**
     * Ringkasan rencana: rentang tanggal, jenis, dan peserta, dengan kata dan pemisah yang sama
     * dengan kepala trip di halaman yang sama (tinjauan Tugas 1 M4). Yang belum disebut pendaki
     * tidak ditulis sama sekali, bukan diisi tebakan (R-007).
     */
    public static function teksRencana(TripPlan $trip): string
    {
        return implode(', ', array_filter([
            KepalaTrip::rentang($trip->planned_date, $trip->end_date),
            $trip->trip_type->labelPendek(),
            $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
        ]));
    }

    /**
     * Hitungan butir persiapan dari koleksi yang sudah dimuat. "Tidak berlaku" tidak
     * ikut dihitung, sama seperti TripPlan::preparationCompletionPercent().
     *
     * @param  Collection<int, TripPreparationItem>  $items
     * @return array{dikonfirmasi: int, berlaku: int, jumlah: int}
     */
    public static function hitungPersiapan(Collection $items): array
    {
        $berlaku = $items->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        return [
            'dikonfirmasi' => $berlaku->where('status', PreparationStatus::CONFIRMED)->count(),
            'berlaku' => $berlaku->count(),
            'jumlah' => $items->count(),
        ];
    }

    /**
     * Kemajuan sebagai hitungan, bukan persentase: "3 dari 8" terbaca sebagai sisa
     * pekerjaan, sedangkan "38%" mudah terbaca sebagai nilai kesiapan (R-005). Daftar yang
     * butirnya ada tetapi seluruhnya Tidak berlaku tidak ditulis "belum ada" (tinjauan Tugas 4
     * M4 dan MIN-2 Tugas 6). Satu kata, "butir", di semua halaman trip, dan "Tidak berlaku"
     * ditulis sebagai nama status, sama dengan daftar persiapan (NIT-B W4-6l).
     *
     * @param  array{dikonfirmasi: int, berlaku: int, jumlah: int}  $persiapan
     */
    public static function teksPersiapan(array $persiapan): string
    {
        return match (true) {
            $persiapan['jumlah'] === 0 => 'Belum ada butir persiapan',
            $persiapan['berlaku'] === 0 => sprintf('Semua %d butir ditandai Tidak berlaku', $persiapan['jumlah']),
            default => sprintf('%d dari %d butir dikonfirmasi', $persiapan['dikonfirmasi'], $persiapan['berlaku']),
        };
    }

    /**
     * Keterangan Persiapan bila SEMUA butir yang belum dikonfirmasi sedang menunggu BMKG, atau
     * null. Butir itu belum dapat dikerjakan sampai prakiraan tanggal trip terbit, jadi
     * Persiapan bukan langkah berikutnya, dan kalimatnya menyebut kapan butir itu terbuka.
     *
     * @param  array{dikonfirmasi: int, berlaku: int, jumlah: int}  $persiapan
     */
    private static function semuaMenungguBmkg(TripPlan $trip, array $persiapan): ?string
    {
        $belum = $persiapan['berlaku'] - $persiapan['dikonfirmasi'];
        $terbit = PreparationService::terbitPrakiraan($trip);
        $menunggu = $trip->preparationItems->filter(fn (TripPreparationItem $item) => PreparationService::menungguBmkg($item, $terbit))->count();

        if ($belum === 0 || $menunggu !== $belum) {
            return null;
        }

        return sprintf('%s; %d butir cuaca menunggu BMKG, terbit mulai %s', self::teksPersiapan($persiapan), $menunggu, $terbit->translatedFormat('D, j M'));
    }

    /**
     * Vonis tersimpan tidak pernah disajikan sebagai keadaan sekarang ketika status
     * resmi jalurnya sudah berubah sejak penilaian. Menyebut label lamanya di samping
     * pengakuan itu memberi pendaki dua jawaban yang bertentangan, jadi labelnya
     * diganti, bukan didampingi.
     *
     * @return array{selesai: bool, keterangan: string, catatan: string|null, peringatan: bool}
     */
    private function kesiapan(TripPlan $trip, ?OfficialStatusValue $statusSekarang): array
    {
        if ($trip->trailIsWithdrawn()) {
            return [
                'selesai' => false,
                'keterangan' => 'Jalur ditarik dari katalog',
                'catatan' => 'Keterangan jalur ini tidak lagi diperbarui di sini. Tanyakan keadaannya langsung kepada pengelola kawasan.',
                'peringatan' => true,
            ];
        }

        if ($trip->readinessIsStale($statusSekarang)) {
            return [
                'selesai' => false,
                'keterangan' => 'Status jalur berubah, perlu dinilai ulang',
                'catatan' => 'Penilaian terakhir dibuat ketika status resminya masih berbeda.',
                'peringatan' => true,
            ];
        }

        $tinjauan = $trip->tinjauanTercatat();

        return [
            'selesai' => $tinjauan !== null,
            'keterangan' => $trip->latestReadinessCheck?->computed_state->label() ?? 'Belum dinilai',
            // Kalimat yang sama dengan Cek kesiapan ("Tinjauan tercatat {waktu}"), dengan format
            // jam yang sama (tinjauan Tugas 7 dan M3 tinjauan akhir W4).
            'catatan' => $tinjauan !== null
                ? 'Tinjauan tercatat '.Timezone::display($tinjauan->updated_at, Timezone::forTrail($trip->trail), 'j M Y H.i').'.'
                : null,
            'peringatan' => false,
        ];
    }
}
