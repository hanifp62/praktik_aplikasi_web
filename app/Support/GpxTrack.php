<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;

/**
 * Membaca jejak jalur dari berkas GPX menjadi daftar [bujur, lintang] (PRD §65, §109).
 *
 * Urutannya bujur dulu agar cocok dengan GeoJSON dan dengan writeLineString(), bukan
 * dengan urutan atribut GPX sendiri yang menulis lat lebih dulu.
 *
 * Jejak yang melebihi batas titik ditolak, bukan diencerkan. Membuang titik dari jalur
 * pendakian berarti memotong tikungan pada fitur navigasi lapangan; kurator yang memilih
 * ketelitiannya di perkakas GPX-nya sendiri, bukan aplikasi ini diam-diam.
 */
class GpxTrack
{
    /**
     * @return array<int, array{0: float, 1: float}>
     *
     * @throws RuntimeException bila berkas tidak dapat dibaca sebagai jejak jalur
     */
    public static function fromXml(string $xml, ?int $maxPoints = null): array
    {
        return static::trackFromXml($xml, $maxPoints)['coordinates'];
    }

    /**
     * Jejak lengkap: koordinat beserta ketinggian tiap titik bila berkasnya membawanya.
     *
     * Ketinggian dahulu dibuang tepat di titik ia masuk, padahal hampir setiap berkas
     * GPX dari perangkat GPS membawanya dan pendaki yang berjalan di jalur itulah yang
     * paling mungkin mengunggahnya. Elevation gain karena itu hanya bisa diketik tangan,
     * sementara angkanya sudah ada di dalam berkas yang sedang diunggah.
     *
     * Ketinggian dapat berisi null: sebagian perkakas menulis trkpt tanpa ele, dan titik
     * tanpa ketinggian tidak boleh menggugurkan garis jalurnya.
     *
     * @return array{coordinates: array<int, array{0: float, 1: float}>, elevations: array<int, float|null>}
     *
     * @throws RuntimeException bila berkas tidak dapat dibaca sebagai jejak jalur
     */
    public static function trackFromXml(string $xml, ?int $maxPoints = null): array
    {
        $maxPoints ??= (int) config('hiking.uploads.gpx_max_points', 5000);

        $dokumen = static::parse($xml);

        // local-name() dipakai karena GPX membawa namespace bawaan, dan berkas dari
        // perkakas berbeda mendaftarkannya dengan prefiks yang berbeda pula.
        $titik = $dokumen->xpath('//*[local-name()="trkpt"]') ?: [];

        if ($titik === []) {
            $titik = $dokumen->xpath('//*[local-name()="rtept"]') ?: [];
        }

        if ($titik === []) {
            throw new RuntimeException(
                'GPX ini tidak memuat titik jalur. Berkas yang hanya berisi waypoint tidak '
                .'membentuk garis jalur.'
            );
        }

        if (count($titik) > $maxPoints) {
            throw new RuntimeException(sprintf(
                'GPX ini memuat terlalu banyak titik (%d, batasnya %d). Kurangi kerapatan '
                .'jejak di perkakas GPX Anda lalu unggah ulang.',
                count($titik),
                $maxPoints
            ));
        }

        $koordinat = array_map(static::koordinat(...), $titik);

        if (count($koordinat) < 2) {
            throw new RuntimeException('Garis jalur butuh minimal dua titik.');
        }

        return [
            'coordinates' => $koordinat,
            'elevations' => array_map(static::ketinggian(...), $titik),
        ];
    }

    /**
     * Total tanjakan dan turunan dari deret ketinggian.
     *
     * Menjumlahkan setiap selisih positif adalah cara yang salah, dan salahnya besar.
     * Ketinggian GPS berderau beberapa meter meskipun perangkatnya diam di satu tempat,
     * sehingga penjumlahan naif mengubah derau menjadi tanjakan: jalur datar sepanjang
     * beberapa jam dapat melaporkan ratusan meter elevation gain yang tidak pernah
     * didaki siapa pun.
     *
     * Karena itu dipakai ambang histeresis. Satu titik acuan disimpan, dan perubahan
     * baru dihitung ketika selisihnya terhadap acuan melewati ambang; acuannya lalu
     * berpindah ke titik itu. Naik-turun yang lebih kecil dari ambang diabaikan
     * seluruhnya, bukan diratakan.
     *
     * Hasilnya taksiran, dan ia bergantung pada ambangnya. Itu sebabnya angkanya
     * ditawarkan kepada kurator untuk disetujui, bukan ditulis diam-diam ke jalur.
     *
     * @param  array<int, float|null>  $ketinggian
     * @return array{gain: int, loss: int}|null null bila berkasnya tidak membawa ketinggian
     */
    public static function gainLoss(array $ketinggian, ?float $ambangMeter = null): ?array
    {
        $ambangMeter ??= (float) config('hiking.uploads.gpx_elevation_threshold_m', 5);
        $terpakai = array_values(array_filter($ketinggian, fn ($nilai) => $nilai !== null));

        if (count($terpakai) < 2) {
            return null;
        }

        $acuan = $terpakai[0];
        $naik = 0.0;
        $turun = 0.0;

        foreach ($terpakai as $nilai) {
            $selisih = $nilai - $acuan;

            if (abs($selisih) < $ambangMeter) {
                continue;
            }

            $selisih > 0 ? $naik += $selisih : $turun += abs($selisih);
            $acuan = $nilai;
        }

        return ['gain' => (int) round($naik), 'loss' => (int) round($turun)];
    }

    /**
     * Profil elevasi untuk digambar: jarak tempuh terhadap ketinggian.
     *
     * Diencerkan sampai sejumlah titik, dan pengenceran di sini sah justru karena
     * gunanya berbeda dari garis jalur. Garis jalur dipakai bernavigasi di lapangan
     * sehingga tikungannya tidak boleh dipotong; profil dipakai membaca bentuk
     * tanjakan, dan seratus titik sudah menggambarkan bentuk yang sama dengan lima ribu.
     *
     * @param  array<int, array{0: float, 1: float}>  $koordinat
     * @param  array<int, float|null>  $ketinggian
     * @return array<int, array{km: float, m: int}>
     */
    public static function profile(array $koordinat, array $ketinggian, int $maxTitik = 120): array
    {
        $jarak = 0.0;
        $mentah = [];

        foreach ($koordinat as $i => $titik) {
            if ($i > 0) {
                $jarak += static::lengthKm([$koordinat[$i - 1], $titik]);
            }

            if (($ketinggian[$i] ?? null) === null) {
                continue;
            }

            $mentah[] = ['km' => round($jarak, 3), 'm' => (int) round($ketinggian[$i])];
        }

        if (count($mentah) <= $maxTitik) {
            return $mentah;
        }

        // Titik pertama dan terakhir selalu ikut: ujung profil yang terpotong mengubah
        // ketinggian awal dan puncaknya, dua angka yang justru paling dibaca.
        $langkah = (count($mentah) - 1) / ($maxTitik - 1);
        $hasil = [];

        for ($i = 0; $i < $maxTitik; $i++) {
            $hasil[] = $mentah[(int) round($i * $langkah)];
        }

        return $hasil;
    }

    /**
     * Panjang jejak dalam kilometer, dihitung dengan haversine seperti mode pendakian.
     *
     * Dipakai kurator untuk membandingkan berkas GPX dengan jarak yang sudah tercatat
     * pada jalur. Selisih besar biasanya berarti berkasnya milik jalur lain.
     *
     * @param  array<int, array{0: float, 1: float}>  $koordinat  pasangan [bujur, lintang]
     */
    public static function lengthKm(array $koordinat): float
    {
        $radius = (int) config('hiking.hike_mode.earth_radius_m', 6371000);
        $meter = 0.0;

        for ($i = 1; $i < count($koordinat); $i++) {
            [$bujurA, $lintangA] = $koordinat[$i - 1];
            [$bujurB, $lintangB] = $koordinat[$i];

            $dLintang = deg2rad($lintangB - $lintangA);
            $dBujur = deg2rad($bujurB - $bujurA);

            $a = sin($dLintang / 2) ** 2
                + cos(deg2rad($lintangA)) * cos(deg2rad($lintangB)) * sin($dBujur / 2) ** 2;

            $meter += $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
        }

        return $meter / 1000;
    }

    private static function parse(string $xml): SimpleXMLElement
    {
        $sebelumnya = libxml_use_internal_errors(true);

        // LIBXML_NOENT sengaja tidak dipasang: berkas GPX datang dari luar, dan
        // substitusi entitas membuka jalan XXE ke berkas server. LIBXML_NONET menutup
        // pengambilan DTD lewat jaringan.
        $dokumen = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        if ($dokumen === false || strtolower($dokumen->getName()) !== 'gpx') {
            throw new RuntimeException('Berkas ini bukan berkas GPX yang dapat dibaca.');
        }

        return $dokumen;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function koordinat(SimpleXMLElement $titik): array
    {
        $lintang = (string) ($titik['lat'] ?? '');
        $bujur = (string) ($titik['lon'] ?? '');

        if (! is_numeric($lintang) || ! is_numeric($bujur)) {
            throw new RuntimeException('Ada titik tanpa koordinat lat/lon yang sah.');
        }

        $lintang = (float) $lintang;
        $bujur = (float) $bujur;

        if ($lintang < -90 || $lintang > 90 || $bujur < -180 || $bujur > 180) {
            throw new RuntimeException(sprintf(
                'Ada koordinat di luar rentang yang sah: %F, %F.',
                $lintang,
                $bujur
            ));
        }

        return [$bujur, $lintang];
    }

    /**
     * Ketinggian satu titik dalam meter, atau null bila titiknya tidak membawanya.
     *
     * Ketinggian yang tidak masuk akal diperlakukan sebagai tidak ada, bukan sebagai
     * galat yang menggugurkan seluruh berkas: satu titik rusak di tengah jejak tidak
     * boleh membuang garis jalur yang selebihnya baik. Batasnya diambil longgar, dari
     * dasar Laut Mati sampai jauh di atas puncak tertinggi dunia.
     */
    private static function ketinggian(SimpleXMLElement $titik): ?float
    {
        $anak = $titik->xpath('./*[local-name()="ele"]');
        $nilai = $anak === [] || $anak === false ? null : trim((string) $anak[0]);

        if ($nilai === null || $nilai === '' || ! is_numeric($nilai)) {
            return null;
        }

        $nilai = (float) $nilai;

        return $nilai < -500 || $nilai > 9000 ? null : $nilai;
    }
}
