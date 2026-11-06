<?php

namespace App\Services;

use App\Enums\TaskOutcome;
use App\Enums\UsabilitySessionKind;
use App\Models\UsabilitySession;
use Illuminate\Support\Collection;

/**
 * Perhitungan di balik protokol uji kegunaan.
 *
 * Seluruh angka di sini punya asalnya, dan asalnya disebut. Yang dijaga bukan
 * ketepatan rumusnya saja, melainkan bahwa angka yang ditampilkan tidak pernah
 * terbaca lebih meyakinkan daripada jumlah peserta yang menopangnya.
 */
class UsabilityStudyService
{
    /**
     * Delapan tugas protokol beserta kriteria berhasil yang dapat diamati.
     *
     * T3 ditandai terpenting karena ia menguji janji utama produk: kalau peserta tidak
     * dapat menjelaskan mengapa sebuah jalur tidak direkomendasikan, seluruh
     * explainability yang dibangun di belakangnya tidak sampai kepada siapa pun.
     *
     * @var array<string, array{judul: string, berhasil: string, inti: bool}>
     */
    public const TUGAS = [
        'T1' => ['judul' => 'Buat akun dan lengkapi profil pendaki', 'berhasil' => 'Profil tersimpan dan peserta sampai di dasbor.', 'inti' => false],
        'T2' => ['judul' => 'Cari tahu jalur mana yang sesuai', 'berhasil' => 'Peserta melihat daftar hasil kecocokan.', 'inti' => false],
        'T3' => ['judul' => 'Jelaskan mengapa satu jalur tidak direkomendasikan', 'berhasil' => 'Peserta menyebut alasannya dengan kata-katanya sendiri.', 'inti' => true],
        'T4' => ['judul' => 'Buat rencana trip dan siapkan perlengkapan', 'berhasil' => 'Trip terbuat dan lima item persiapan dikonfirmasi.', 'inti' => false],
        'T5' => ['judul' => 'Periksa apakah sudah siap berangkat', 'berhasil' => 'Peserta sampai di halaman kesiapan dan menyebut apa yang kurang.', 'inti' => true],
        'T6' => ['judul' => 'Cari status resmi jalur dan sumbernya', 'berhasil' => 'Peserta menemukan status beserta sumber dan tanggalnya.', 'inti' => false],
        'T7' => ['judul' => 'Buka aplikasi tanpa sinyal', 'berhasil' => 'Peserta memahami data lama sengaja tidak ditampilkan.', 'inti' => false],
        'T8' => ['judul' => 'Tolak satu laporan komunitas beserta alasannya', 'berhasil' => 'Laporan ditolak dan alasannya tersimpan.', 'inti' => false],
    ];

    /**
     * Sepuluh pernyataan SUS, berselang-seling positif dan negatif.
     *
     * Selang-seling itu bukan gaya penulisan melainkan bagian dari instrumennya: ia
     * memaksa responden membaca tiap pernyataan alih-alih mencentang satu kolom lurus
     * ke bawah, dan rumus penskorannya bergantung padanya.
     *
     * @var array<int, string>
     */
    public const PERNYATAAN_SUS = [
        1 => 'Saya rasa saya akan sering memakai aplikasi ini.',
        2 => 'Saya merasa aplikasi ini terlalu rumit.',
        3 => 'Saya rasa aplikasi ini mudah dipakai.',
        4 => 'Saya rasa saya butuh bantuan orang teknis untuk bisa memakai aplikasi ini.',
        5 => 'Saya rasa berbagai bagian aplikasi ini menyatu dengan baik.',
        6 => 'Saya rasa ada terlalu banyak hal yang tidak konsisten di aplikasi ini.',
        7 => 'Saya rasa kebanyakan orang akan cepat bisa memakai aplikasi ini.',
        8 => 'Saya rasa aplikasi ini sangat merepotkan dipakai.',
        9 => 'Saya merasa percaya diri saat memakai aplikasi ini.',
        10 => 'Saya perlu belajar banyak dulu sebelum bisa memakai aplikasi ini.',
    ];

    /**
     * Peluang satu peserta menemukan satu masalah kegunaan tertentu.
     *
     * Angka Nielsen dan Landauer dari analisis sebelas kajian. Dari sinilah kurva
     * 1-(1-p)^n berasal, dan dari kurva itulah angka "lima peserta" yang sering dikutip.
     */
    private const PELUANG_TEMU = 0.31;

    /** Lima peserta menutup sekitar 84% masalah; penambahan sesudahnya makin landai. */
    public const TARGET_PESERTA_TUGAS = 5;

    /** Di bawah ini rata-rata SUS terlalu goyah untuk dilaporkan sebagai angka. */
    public const MINIMUM_RESPONDEN_SUS = 12;

    /**
     * Skor SUS satu responden, 0 sampai 100.
     *
     * Bukan persentase, dan tidak boleh ditulis dengan tanda persen. Pernyataan ganjil
     * dikurangi satu, pernyataan genap dikurangkan dari lima, jumlahnya dikali 2,5.
     *
     * @param  array<int, int|string|null>  $jawaban  sepuluh jawaban skala 1-5
     */
    public function susScore(array $jawaban): ?float
    {
        $jumlah = 0;

        for ($nomor = 1; $nomor <= 10; $nomor++) {
            // Hanya kunci 1-10 yang diterima. Sempat ada cadangan ke kunci sebelumnya
            // agar array berindeks nol ikut terbaca, dan akibatnya lembar sembilan
            // jawaban memungut jawaban nomor sembilan sebagai nomor sepuluh lalu
            // menghasilkan skor yang terlihat sah.
            $nilai = $jawaban[$nomor] ?? null;

            if (! is_numeric($nilai) || (int) $nilai < 1 || (int) $nilai > 5) {
                return null;
            }

            $nilai = (int) $nilai;
            $jumlah += $nomor % 2 === 1 ? $nilai - 1 : 5 - $nilai;
        }

        return round($jumlah * 2.5, 1);
    }

    /**
     * Huruf mutu SUS menurut kurva Sauro dan Lewis.
     *
     * Kurva, bukan persentase: 68 adalah rata-rata seluruh produk yang pernah diukur,
     * jadi 68 bernilai C dan bukan D. Membacanya sebagai nilai ujian membuat produk
     * yang persis rata-rata terlihat nyaris gagal.
     */
    public function grade(float $score): string
    {
        return match (true) {
            $score >= 84.1 => 'A+',
            $score >= 80.8 => 'A',
            $score >= 78.9 => 'A-',
            $score >= 77.2 => 'B+',
            $score >= 74.1 => 'B',
            $score >= 72.6 => 'B-',
            $score >= 71.1 => 'C+',
            $score >= 65.0 => 'C',
            $score >= 62.7 => 'C-',
            $score >= 51.7 => 'D',
            default => 'F',
        };
    }

    /**
     * Perkiraan bagian masalah kegunaan yang tertemukan oleh n peserta.
     *
     * Kurva ini yang membuat "sudah lima peserta" berarti sesuatu, dan yang membuat
     * peserta keenam sampai kesepuluh tetap layak meski hasilnya makin sedikit.
     */
    public function discoveryRate(int $peserta): float
    {
        if ($peserta <= 0) {
            return 0.0;
        }

        return round(1 - (1 - self::PELUANG_TEMU) ** $peserta, 4);
    }

    /**
     * Ringkasan seluruh studi: apa yang sudah diketahui, dan seberapa jauh ia boleh
     * dipercaya.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $sesi = UsabilitySession::query()->get();
        $sesiTugas = $sesi->where('kind', UsabilitySessionKind::TASK);
        $skorSus = $sesi->whereNotNull('sus_score')->pluck('sus_score');

        $rataSus = $skorSus->isEmpty() ? null : round($skorSus->avg(), 1);

        return [
            'peserta_tugas' => $sesiTugas->count(),
            'responden_sus' => $skorSus->count(),
            'sesi_pembaca_layar' => $sesi->where('kind', UsabilitySessionKind::SCREEN_READER)->count(),
            'cakupan_masalah' => $this->discoveryRate($sesiTugas->count()),
            'peserta_kurang' => max(0, self::TARGET_PESERTA_TUGAS - $sesiTugas->count()),
            'responden_sus_kurang' => max(0, self::MINIMUM_RESPONDEN_SUS - $skorSus->count()),
            'sus_rata' => $rataSus,
            'sus_grade' => $rataSus === null ? null : $this->grade($rataSus),
            // Rata-rata di bawah minimum tetap ditampilkan, tetapi ditandai agar tidak
            // dikutip sebagai temuan. Menyembunyikannya justru membuat peneliti
            // menghitung sendiri di luar sistem, tanpa penanda apa pun.
            'sus_dapat_diandalkan' => $skorSus->count() >= self::MINIMUM_RESPONDEN_SUS,
            'tugas' => $this->taskBreakdown($sesiTugas),
        ];
    }

    /**
     * Tingkat keberhasilan per tugas, dengan setengah nilai untuk yang berhasil tetapi
     * tersendat.
     *
     * @param  Collection<int, UsabilitySession>  $sesi
     * @return array<string, array<string, mixed>>
     */
    private function taskBreakdown($sesi): array
    {
        $ringkas = [];

        foreach (self::TUGAS as $kode => $tugas) {
            $hasil = $sesi
                ->map(fn (UsabilitySession $s) => $s->task_results[$kode]['outcome'] ?? null)
                ->filter()
                ->map(fn (string $nilai) => TaskOutcome::tryFrom($nilai))
                ->filter();

            $ringkas[$kode] = [
                'judul' => $tugas['judul'],
                'inti' => $tugas['inti'],
                'diamati' => $hasil->count(),
                'gagal' => $hasil->filter(fn (TaskOutcome $o) => $o === TaskOutcome::FAILED)->count(),
                'tingkat_berhasil' => $hasil->isEmpty()
                    ? null
                    : round($hasil->sum(fn (TaskOutcome $o) => $o->credit()) / $hasil->count(), 3),
            ];
        }

        return $ringkas;
    }
}
