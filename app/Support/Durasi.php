<?php

namespace App\Support;

/**
 * Durasi pendakian, ditulis satu cara.
 *
 * Sebelum berkas ini ada, tiga halaman memformat durasi dengan tiga cara: "1 jam 30
 * menit" di perjalanan antarpos, "5.5 jam" di hasil rekomendasi, dan "5.5 jam" lagi di
 * perbandingan jalur. Pendaki yang menimbang dua jalur di dua halaman menimbang dua
 * format.
 *
 * Desimal jam ditinggalkan. Tidak ada yang memikirkan waktu berjalan sebagai "5,5 jam";
 * yang dipikirkan "lima setengah jam", dan bentuk yang paling dekat dengan itu adalah
 * jam beserta menitnya.
 */
class Durasi
{
    /**
     * Bentuk penuh, untuk tempat yang ruangnya cukup.
     *
     * Nol diperlakukan sebagai belum diketahui, bukan sebagai seketika. Durasi nol pada
     * sebuah jalur berarti tidak ada yang pernah mengisinya, dan menyajikannya sebagai
     * nol menyatakan sesuatu yang salah tentang jalurnya (PRD §91).
     */
    public static function panjang(?int $menit): string
    {
        if ($menit === null || $menit <= 0) {
            return 'belum diketahui';
        }

        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        if ($jam === 0) {
            return $sisa.' menit';
        }

        // Dibangun sebagai potongan lalu disambung, bukan dipangkas dari untaian jadi.
        // Versi sebelumnya di komponen perjalanan antarpos memakai rtrim untuk membuang
        // " 0 menit", dan rtrim memperlakukan argumennya sebagai kumpulan karakter,
        // sehingga "1 jam 0 menit" terkikis menjadi "1 ja".
        return $sisa === 0 ? $jam.' jam' : $jam.' jam '.$sisa.' menit';
    }

    /**
     * Bentuk pendek, untuk baris daftar tempat ruangnya sempit dan mata memindai.
     *
     * Di sini desimal justru tepat: yang dibutuhkan perbandingan sekilas antarbaris,
     * bukan angka untuk direncanakan. Koma, bukan titik, karena angkanya dibaca orang
     * Indonesia.
     */
    public static function pendek(?int $menit): string
    {
        if ($menit === null || $menit <= 0) {
            return '-';
        }

        if ($menit < 60) {
            return $menit.' m';
        }

        $jam = $menit / 60;

        return rtrim(rtrim(number_format($jam, 1, ',', '.'), '0'), ',').' j';
    }
}
