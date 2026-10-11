<?php

namespace App\Services\Trails;

/**
 * Profil elevasi beberapa jalur pada satu skala, untuk digambar berdampingan: garis profil
 * kartu Jelajah, profil di tabel banding, dan visual data halaman gunung (spec v3 bagian 6.2,
 * 6.3, 6.7; "grafik: skala sama saat membandingkan").
 *
 * Titiknya dinormalkan di server menjadi 0..1, jadi klien hanya menggambar dan tidak pernah
 * menghitung skala sendiri. Satu profil yang tidak dapat digambar (kurang dari dua titik sah)
 * dikirim sebagai tidak ada, bukan garis datar karangan (R-007). Profil panjang dijarangkan
 * menjadi paling banyak MAKS_TITIK titik: bentuknya tetap, ukuran halamannya tidak tumbuh
 * bersama resolusi rekaman GPS.
 */
final class SkalaProfil
{
    public const MAKS_TITIK = 48;

    /**
     * Titik sah sebuah profil, [km, m], urut sepanjang jalur; null bila tidak dapat digambar.
     *
     * @param  array<int, mixed>|null  $profil
     * @return array<int, array{0: float, 1: float}>|null
     */
    public static function titik(?array $profil): ?array
    {
        $titik = [];

        foreach ((array) $profil as $t) {
            if (is_array($t) && is_numeric($t['km'] ?? null) && is_numeric($t['m'] ?? null)) {
                $titik[] = [(float) $t['km'], (float) $t['m']];
            }
        }

        usort($titik, fn (array $a, array $b) => $a[0] <=> $b[0]);

        return count($titik) >= 2 && end($titik)[0] > $titik[0][0] ? $titik : null;
    }

    /**
     * Skala naik yang sama untuk setiap profil: setiap garis memenuhi lebarnya sendiri, dan
     * tingginya diukur dalam meter yang sama. Jalur yang naik 280 m terbaca landai di sebelah
     * jalur yang naik 1.650 m, persis seperti di prototype 02 dan 07.
     *
     * @param  array<int, array<int, mixed>|null>  $profilPerJalur  berkunci id jalur
     * @return array{skalaMeter: int, garis: array<int, array<int, array{0: float, 1: float}>>}
     */
    public static function naikBersama(array $profilPerJalur): array
    {
        $bersih = array_filter(array_map(self::titik(...), $profilPerJalur));
        $rentang = array_map(fn (array $t) => max(array_column($t, 1)) - min(array_column($t, 1)), $bersih);
        $skala = max([1.0, ...array_values($rentang)]);

        $garis = [];

        foreach ($bersih as $id => $titik) {
            $awal = $titik[0][0];
            $panjang = end($titik)[0] - $awal;
            $terendah = min(array_column($titik, 1));

            $garis[$id] = self::jarangkan(array_map(fn (array $t) => [
                round(($t[0] - $awal) / $panjang, 3),
                round(($t[1] - $terendah) / $skala, 3),
            ], $titik));
        }

        return ['skalaMeter' => (int) round($skala), 'garis' => $garis];
    }

    /**
     * Skala mutlak bersama untuk jalur di satu gunung: jarak dan ketinggian diukur sama untuk
     * semuanya, jadi jalur yang lebih panjang tergambar lebih panjang dan puncak yang sama
     * berada di ketinggian yang sama.
     *
     * @param  array<int, array<int, mixed>|null>  $profilPerJalur  berkunci id jalur
     * @return array{kmMaks: float, mMin: int, mMaks: int, garis: array<int, array<int, array{0: float, 1: float}>>}
     */
    public static function mutlakBersama(array $profilPerJalur): array
    {
        $bersih = array_filter(array_map(self::titik(...), $profilPerJalur));

        if ($bersih === []) {
            return ['kmMaks' => 0.0, 'mMin' => 0, 'mMaks' => 0, 'garis' => []];
        }

        $semua = array_merge(...array_values($bersih));
        $kmMaks = max(array_map(fn (array $t) => end($t)[0] - $t[0][0], $bersih));
        $mMin = min(array_column($semua, 1));
        $mMaks = max(array_column($semua, 1));
        $tinggi = max(1.0, $mMaks - $mMin);

        $garis = [];

        foreach ($bersih as $id => $titik) {
            $awal = $titik[0][0];

            $garis[$id] = self::jarangkan(array_map(fn (array $t) => [
                round(($t[0] - $awal) / $kmMaks, 3),
                round(($t[1] - $mMin) / $tinggi, 3),
            ], $titik));
        }

        return ['kmMaks' => round($kmMaks, 1), 'mMin' => (int) round($mMin), 'mMaks' => (int) round($mMaks), 'garis' => $garis];
    }

    /**
     * Paling banyak MAKS_TITIK titik, dengan titik pertama dan terakhir selalu ikut.
     *
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @return array<int, array{0: float, 1: float}>
     */
    public static function jarangkan(array $titik): array
    {
        $jumlah = count($titik);

        if ($jumlah <= self::MAKS_TITIK) {
            return $titik;
        }

        $langkah = ($jumlah - 1) / (self::MAKS_TITIK - 1);
        $hasil = [];

        for ($i = 0; $i < self::MAKS_TITIK; $i++) {
            $hasil[] = $titik[(int) round($i * $langkah)];
        }

        return $hasil;
    }
}
