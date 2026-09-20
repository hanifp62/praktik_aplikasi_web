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

        return $koordinat;
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
}
