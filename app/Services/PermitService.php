<?php

namespace App\Services;

use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Support\Timezone;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Aturan perizinan pendakian (PRD §36 Logistik).
 *
 * Sistem tidak memesan izin, tidak memverifikasi kuota, dan tidak menyimpan jatah
 * apa pun. Data internal tidak akan pernah sinkron dengan sistem resmi, dan
 * menampilkan sisa kuota yang keliru justru lebih berbahaya daripada tidak
 * menampilkannya sama sekali (PRD §43: kita bukan sumber otoritatif).
 *
 * Yang dikerjakan di sini hanya satu: memberi tahu pendaki bahwa rencananya
 * berbenturan dengan jendela pemesanan, dan menunjukkan ke mana harus mengurus.
 */
class PermitService
{
    /**
     * Aturan yang berlaku untuk sebuah jalur: aturan khusus jalur lebih dulu,
     * lalu jatuh ke aturan tingkat gunung.
     */
    public function requirementFor(Trail $trail): ?PermitRequirement
    {
        return PermitRequirement::query()
            ->where('trail_id', $trail->id)
            ->terverifikasiDulu()
            ->first()
            ?? PermitRequirement::query()
                ->whereNull('trail_id')
                ->where('mountain_id', $trail->mountain_id)
                ->terverifikasiDulu()
                ->first();
    }

    /**
     * Aturan perizinan untuk banyak jalur sekaligus, dalam satu query.
     *
     * Versi per-jalur membutuhkan dua query masing-masing; dipakai di dalam perulangan
     * kandidat, cara itu mengembalikan pertumbuhan linear yang baru saja dihapus dari mesin
     * rekomendasi.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, PermitRequirement|null> berkunci id jalur
     */
    public function requirementsForTrails(Collection $trails): array
    {
        $resolved = array_fill_keys($trails->pluck('id')->all(), null);

        if ($trails->isEmpty()) {
            return $resolved;
        }

        $rules = PermitRequirement::query()
            ->where(function ($query) use ($trails) {
                $query->whereIn('trail_id', $trails->pluck('id'))
                    ->orWhereIn('mountain_id', $trails->pluck('mountain_id')->filter()->unique());
            })
            ->terverifikasiDulu()
            ->get();

        foreach ($trails as $trail) {
            // Aturan khusus jalur lebih diutamakan daripada aturan tingkat gunung.
            $resolved[$trail->id] = $rules->firstWhere('trail_id', $trail->id)
                ?? $rules->first(fn (PermitRequirement $rule) => $rule->trail_id === null
                    && $rule->mountain_id === $trail->mountain_id);
        }

        return $resolved;
    }

    /**
     * Peringatan bila tanggal target jatuh di luar jendela pemesanan.
     *
     * Mengembalikan null ketika tidak ada aturan tercatat. Ketiadaan data bukan
     * alasan untuk mengarang peringatan, PRD §95 berlaku dua arah: tidak tahu
     * tidak sama dengan bermasalah.
     */
    public function bookingWarningFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?string {
        $jendela = $this->bookingWindowFor($trail, $targetDate, $requirement);

        // Hanya jendela yang menghalangi (sudah tutup atau belum dibuka) yang menjadi
        // peringatan. Jendela terbuka tempatnya di halaman trip sebagai ajakan bertindak, dan
        // jendela yang tanggal bukanya belum tercatat bukan hambatan yang diketahui.
        return $jendela !== null && in_array($jendela['state'], [self::BOOKING_DITUTUP, self::BOOKING_BELUM_DIBUKA], true)
            ? $jendela['message']
            : null;
    }

    public const BOOKING_BELUM_DIBUKA = 'BELUM_DIBUKA';

    public const BOOKING_TERBUKA = 'TERBUKA';

    public const BOOKING_DITUTUP = 'DITUTUP';

    /**
     * Belum tutup menurut catatan, tetapi tanggal bukanya tidak tercatat (ruling W4-FE, R-007):
     * tidak pernah ditulis "sedang dibuka", karena itu mengisi data yang hilang dengan asumsi.
     */
    public const BOOKING_BUKA_BELUM_TERCATAT = 'BUKA_BELUM_TERCATAT';

    /**
     * Posisi tanggal rencana terhadap jendela pemesanan izin.
     *
     * Aritmetikanya hanya ada di sini. Jendela ini bergerak relatif terhadap tanggal
     * yang sudah dipilih, jadi jawabannya berubah seiring hari berjalan meskipun tidak
     * ada satu pun data yang disunting: itulah sebabnya ia perlu dihitung ulang setiap
     * halaman trip dibuka, bukan disimpan.
     *
     * `buka` dan `tutup` adalah tanggal kalender H-buka dan H-tutup untuk tanggal rencana itu
     * (hari pertama dan hari terakhir pemesanan), atau null bila aturannya tidak mencatatnya;
     * halaman menuliskannya tanpa menghitung ulang (tinjauan Tugas 3 M8).
     *
     * @return array{state: string, message: string, requirement: PermitRequirement, buka: Carbon|null, tutup: Carbon|null}|null
     */
    public function bookingWindowFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?array {
        if ($targetDate === null) {
            return null;
        }

        // false berarti "belum dicari"; null berarti "sudah dicari, memang tidak ada".
        // Pembedaan ini yang memungkinkan hasil batch dioper tanpa query ulang.
        $requirement = $requirement === false ? $this->requirementFor($trail) : $requirement;

        if ($requirement === null) {
            return null;
        }

        // now() menyelesaikan "hari ini" menurut zona aplikasi (UTC, config/app.php),
        // dan tidak ada pendaki yang hidup di UTC: selama tujuh jam setiap hari tanggal
        // UTC masih kemarin bagi WIB, sehingga daysAhead terhitung satu lebih banyak dan
        // jendela pemesanan yang sebenarnya sudah tertutup masih tampak terbuka.
        //
        // Ini tenggat yang bergerak maju (forward-looking): sistem tidak boleh pernah
        // membuat sisa waktu tampak lebih longgar daripada kenyataan. WIB adalah zona
        // Indonesia yang paling akhir berganti hari, jadi tanggalnya selalu yang terkecil
        // di antara ketiganya; memakainya sebagai "hari ini" membuat daysAhead tidak
        // pernah dihitung lebih kecil daripada yang dialami pendaki di zona mana pun
        // (lihat docblock Timezone::earliestDateInIndonesia()).
        $today = Carbon::parse(Timezone::earliestDateInIndonesia());

        $daysAhead = (int) $today->diffInDays($targetDate->copy()->startOfDay(), false);

        if ($daysAhead < 0) {
            return null;
        }

        $closes = $requirement->booking_closes_days_before;
        $opens = $requirement->booking_opens_days_before;
        $tanggal = Carbon::parse($targetDate->toDateString());
        $dasar = [
            'requirement' => $requirement,
            'buka' => $opens !== null ? $tanggal->copy()->subDays($opens) : null,
            'tutup' => $closes !== null ? $tanggal->copy()->subDays($closes) : null,
        ];

        if ($closes !== null && $daysAhead < $closes) {
            return $dasar + [
                'state' => self::BOOKING_DITUTUP,
                'message' => $this->message($requirement, sprintf(
                    'Pemesanan izin untuk jalur ini sudah ditutup H-%d, sedangkan tanggal rencana Anda tinggal %d hari lagi.',
                    $closes,
                    $daysAhead
                )),
            ];
        }

        if ($opens !== null && $daysAhead > $opens) {
            return $dasar + [
                'state' => self::BOOKING_BELUM_DIBUKA,
                'message' => $this->message($requirement, sprintf(
                    'Pemesanan izin untuk jalur ini belum dibuka, baru dibuka H-%d, sedangkan tanggal rencana Anda masih %d hari lagi.',
                    $opens,
                    $daysAhead
                )),
            ];
        }

        // Tanggal buka kosong tidak pernah jatuh ke "sedang dibuka" (R-007, ruling W4-FE).
        if ($opens === null) {
            return $dasar + [
                'state' => self::BOOKING_BUKA_BELUM_TERCATAT,
                'message' => $this->message($requirement, $closes !== null
                    ? sprintf(
                        'Tanggal buka pemesanan izin untuk jalur ini belum tercatat; pemesanan ditutup H-%d, yaitu %d hari lagi.',
                        $closes,
                        max(0, $daysAhead - $closes)
                    )
                    : 'Jendela pemesanan izin untuk jalur ini belum tercatat.'),
            ];
        }

        return $dasar + [
            'state' => self::BOOKING_TERBUKA,
            'message' => $this->message($requirement, $closes !== null
                ? sprintf(
                    'Pemesanan izin untuk jalur ini sedang dibuka dan ditutup H-%d, yaitu %d hari lagi.',
                    $closes,
                    max(0, $daysAhead - $closes)
                )
                : 'Pemesanan izin untuk jalur ini sedang dibuka.'),
        ];
    }

    /**
     * Keterangan dan ajakan pemesanan izin per keadaan jendela, satu penentu untuk Persiapan,
     * Rencanakan, dan Cek kesiapan (M1 tinjauan akhir W4; ruang kerja menyusul di W8).
     *
     * - Terbuka: ajakan "Pesan di sistem resmi", dengan hari terakhirnya bila tercatat.
     * - Tanggal buka belum tercatat: tautan netral "Periksa di sistem resmi", bukan ajakan
     *   memesan, karena tidak diketahui apakah pemesanannya sudah dibuka.
     * - Sudah tutup atau belum dibuka: tanpa tautan; keterangannya menyebut hari terakhir atau
     *   hari pertama pemesanan.
     * - Tanggal yang sudah lewat, aturan tanpa alamat pemesanan, atau trip yang sudah selesai
     *   atau batal ($bolehMemesan false): tanpa tautan.
     *
     * Keterangannya tanpa alamat pemesanan mentah, dan kuota yang belum terverifikasi disebut
     * begitu (tinjauan Tugas 4 M9, lewat PermitRequirement::summary()). Memount menautkan,
     * tidak memesan (§43).
     *
     * @return array{keadaan: string|null, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}
     */
    public function pemesanan(Trail $trail, ?CarbonInterface $tanggal, PermitRequirement $aturan, bool $bolehMemesan = true): array
    {
        return self::pemesananDariJendela($this->bookingWindowFor($trail, $tanggal, $aturan), $aturan, $bolehMemesan);
    }

    /**
     * Penentu yang sama dengan pemesanan(), untuk jendela yang sudah dihitung pemanggilnya.
     * Rencanakan menulis judul dan tautan dari SATU jendela, jadi "hari ini" hanya dibaca sekali
     * dan pergantian tengah malam tidak dapat membuat keduanya berasal dari dua keadaan
     * (W4-FK N4).
     *
     * @param  array{state: string, message: string, requirement: PermitRequirement, buka: Carbon|null, tutup: Carbon|null}|null  $jendela  hasil bookingWindowFor()
     * @return array{keadaan: string|null, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}
     */
    public static function pemesananDariJendela(?array $jendela, PermitRequirement $aturan, bool $bolehMemesan = true): array
    {
        $ringkas = $aturan->summary();
        $tutup = $jendela['tutup'] ?? null;
        $hariTerakhir = $tutup !== null ? ', hari terakhirnya '.self::hari($tutup) : '';
        $dapatDitautkan = $bolehMemesan && filled($aturan->booking_url);

        return match ($jendela['state'] ?? null) {
            self::BOOKING_TERBUKA => [
                'keadaan' => self::BOOKING_TERBUKA,
                'tautan' => $dapatDitautkan ? [
                    'href' => $aturan->booking_url,
                    'teks' => 'Pesan di sistem resmi'.($tutup !== null ? ', paling lambat '.self::hari($tutup) : ''),
                    'label' => 'Pesan',
                ] : null,
                'keterangan' => $ringkas,
            ],
            self::BOOKING_BUKA_BELUM_TERCATAT => [
                'keadaan' => self::BOOKING_BUKA_BELUM_TERCATAT,
                'tautan' => $bolehMemesan ? self::tautanPeriksa($aturan) : null,
                'keterangan' => $ringkas.'. Tanggal buka pemesanan belum tercatat di Memount'.$hariTerakhir.'.',
            ],
            self::BOOKING_DITUTUP => [
                'keadaan' => self::BOOKING_DITUTUP,
                'tautan' => null,
                'keterangan' => $ringkas.'. Pemesanan di sistem resmi sudah tutup'.$hariTerakhir.'.',
            ],
            self::BOOKING_BELUM_DIBUKA => [
                'keadaan' => self::BOOKING_BELUM_DIBUKA,
                'tautan' => null,
                'keterangan' => $ringkas.'. Pemesanan di sistem resmi dibuka mulai '.self::hari($jendela['buka']).'.',
            ],
            default => ['keadaan' => null, 'tautan' => null, 'keterangan' => $ringkas],
        };
    }

    /**
     * Tautan netral ke sistem resmi ketika jendela pemesanannya tidak diketahui: "Periksa",
     * bukan ajakan memesan, karena tidak diketahui apakah pemesanannya sudah dibuka.
     *
     * @return array{href: string, teks: string, label: string}|null
     */
    public static function tautanPeriksa(PermitRequirement $aturan): ?array
    {
        return filled($aturan->booking_url)
            ? ['href' => $aturan->booking_url, 'teks' => 'Periksa di sistem resmi', 'label' => 'Periksa']
            : null;
    }

    /**
     * Kalimat penutup kotak izin tentang Memount menautkan atau tidak sistem resmi, menurut ada
     * atau tidaknya tautan yang SUNGGUH tampil pada elemen yang sama (ruling W5-G1). Mengklaim
     * tautan yang tidak ditampilkan melanggar R-007, jadi pemanggil wajib mengoper hasil tautan
     * yang sama dengan yang dirender, bukan menilai ulang. Sumber tunggal untuk Beranda
     * (TenggatTrip) dan Rencanakan (RencanaTrip); klien memakai kalimat yang sama persis
     * (KartuIzin.tsx:54).
     */
    public static function kalimatTautan(bool $adaTautan): string
    {
        return $adaTautan
            ? 'Memount menautkan sistem resmi dan tidak menjual izin.'
            : 'Memount tidak memesan atau menjual izin.';
    }

    /**
     * Hari kalender pemesanan, cara tulis yang sama di semua halaman trip.
     */
    public static function hari(CarbonInterface $tanggal): string
    {
        return $tanggal->translatedFormat('D, j M');
    }

    /**
     * Pasangan bookingWarningFor untuk keadaan sebaliknya: rencana tanpa tanggal.
     *
     * Keduanya saling meniadakan, jadi jalur berizin selalu mendapat tepat satu
     * kalimat tentang pemesanan. Tanpa ini, tidak adanya peringatan pada rencana tak
     * bertanggal terbaca sebagai "izin aman", padahal artinya tidak ada yang bisa
     * dibandingkan dengan jendela pemesanan (PRD §95).
     */
    public function uncheckedWindowNoticeFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?string {
        if ($targetDate !== null) {
            return null;
        }

        $requirement = $requirement === false ? $this->requirementFor($trail) : $requirement;

        if ($requirement === null) {
            return null;
        }

        if ($requirement->booking_opens_days_before === null && $requirement->booking_closes_days_before === null) {
            return null;
        }

        return $this->message(
            $requirement,
            'Jalur ini mewajibkan izin, dan jendela pemesanannya belum diperiksa karena rencana Anda belum bertanggal.'
        );
    }

    private function message(PermitRequirement $requirement, string $situation): string
    {
        $message = sprintf('%s Penyelenggara: %s.', $situation, $requirement->authority);

        if ($requirement->booking_url) {
            $message .= ' Pemesanan: '.$requirement->booking_url;
        }

        return $message;
    }
}
