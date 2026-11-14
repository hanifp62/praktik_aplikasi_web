<?php

namespace Tests\Unit;

use App\Services\Trails\PosSepanjangJalur;
use PHPUnit\Framework\TestCase;

/**
 * Penjaga km pos sepanjang garis rute (titipan W3 T6-M8 dan m11; ruling W8-R21), tanpa basis data:
 * baris yang diberikan sudah urut menurut urutan pos, persis keluaran kueri PostGIS-nya. Angka yang
 * tampak pasti tetapi keliru lebih buruk daripada tanpa angka (R-005, R-007).
 */
class PosSepanjangJalurGuardTest extends TestCase
{
    /**
     * @return object{id: int, km: float|null, jarak_m: float|null}
     */
    private static function pos(int $id, ?float $km, ?float $jarak = 10.0): object
    {
        return (object) ['id' => $id, 'km' => $km, 'jarak_m' => $jarak];
    }

    public function test_posts_on_the_line_get_their_distance(): void
    {
        $this->assertSame([1 => 0.0, 2 => 2.4, 3 => 5.1], PosSepanjangJalur::saring([self::pos(1, 0.0), self::pos(2, 2.36), self::pos(3, 5.12)], 150));
    }

    public function test_a_post_far_from_the_line_gets_no_distance(): void
    {
        $this->assertSame([1 => 0.0, 3 => 5.1], PosSepanjangJalur::saring([self::pos(1, 0.0), self::pos(2, 2.4, 420.0), self::pos(3, 5.12)], 150));
    }

    public function test_a_backwards_post_withholds_every_distance(): void
    {
        $this->assertSame([], PosSepanjangJalur::saring([self::pos(1, 0.0), self::pos(2, 4.0), self::pos(3, 1.2)], 150));
    }

    /**
     * Dua pos di titik yang sama, atau selisih proyeksi di bawah kelonggaran, bukan urutan mundur.
     */
    public function test_equal_or_nearly_equal_distances_are_not_backwards(): void
    {
        $this->assertSame([1 => 1.0, 2 => 1.0, 3 => 1.0], PosSepanjangJalur::saring([self::pos(1, 1.0), self::pos(2, 1.0), self::pos(3, 0.97)], 150));
    }

    public function test_a_post_without_a_projection_is_skipped(): void
    {
        $this->assertSame([2 => 1.5], PosSepanjangJalur::saring([self::pos(1, null), self::pos(2, 1.5), self::pos(3, 2.0, null)], 150));
    }
}
