<?php

namespace Tests\Unit;

use App\Support\Durasi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Durasi ditulis satu cara di seluruh aplikasi.
 *
 * Diukur sebelum diperbaiki: tiga berkas memformat durasi dengan tiga cara berbeda.
 * "1 jam 30 menit" di perjalanan antarpos, "5.5 jam" di hasil rekomendasi, "5.5 jam"
 * lagi di perbandingan jalur. Pendaki yang membandingkan dua jalur di dua halaman
 * membandingkan dua format, dan angka desimal jam adalah format yang tidak dipakai
 * siapa pun untuk memikirkan waktu berjalan.
 */
class DurasiTest extends TestCase
{
    /**
     * @return array<string, array{0: ?int, 1: string}>
     */
    public static function contoh(): array
    {
        return [
            'belum diketahui' => [null, 'belum diketahui'],
            'nol' => [0, 'belum diketahui'],
            'kurang dari sejam' => [45, '45 menit'],
            'tepat sejam' => [60, '1 jam'],
            'sejam lewat' => [90, '1 jam 30 menit'],
            'beberapa jam bulat' => [300, '5 jam'],
            'beberapa jam lewat' => [325, '5 jam 25 menit'],
            'lebih dari sehari' => [1500, '25 jam'],
        ];
    }

    #[DataProvider('contoh')]
    public function test_it_reads_the_way_a_hiker_thinks_about_time(?int $menit, string $harapan): void
    {
        $this->assertSame($harapan, Durasi::panjang($menit));
    }

    /**
     * Bentuk pendek untuk baris daftar, tempat ruangnya sempit dan mata memindai.
     */
    public function test_the_short_form_drops_the_minutes_once_it_passes_an_hour(): void
    {
        $this->assertSame('5 j', Durasi::pendek(300));
        $this->assertSame('5,5 j', Durasi::pendek(330));
        $this->assertSame('45 m', Durasi::pendek(45));
        $this->assertSame('-', Durasi::pendek(null));
    }

    /**
     * Nol menit bukan "0 jam". Durasi nol untuk sebuah jalur berarti tidak ada yang
     * pernah mengisinya, dan menyajikannya sebagai nol menyatakan sesuatu yang salah
     * tentang jalurnya (PRD §91).
     */
    public function test_zero_is_treated_as_unknown_not_as_instant(): void
    {
        $this->assertSame('belum diketahui', Durasi::panjang(0));
        $this->assertSame('-', Durasi::pendek(0));
    }
}
