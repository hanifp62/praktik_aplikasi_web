<?php

namespace Tests\Spatial;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Kunci jalan CronController di store cache `database`, store yang dipakai di Vercel, pada PostgreSQL (R-024).
 *
 * Suite utama memakai store `array`, jadi jalur yang diperebutkan tidak pernah menyentuh PostgreSQL: INSERT yang gagal
 * karena kuncinya sudah ada, lalu UPDATE bersyarat yang mengambil alih kunci kedaluwarsa. DatabaseLock tidak memakai
 * savepoint, dan di PostgreSQL INSERT yang gagal membatalkan transaksi yang sedang berjalan, sehingga UPDATE sesudahnya
 * ikut gagal. CronController memakai kunci ini di luar transaksi; test ini juga, karena itu tanpa transaksi
 * RefreshDatabase, dan membersihkan barisnya sendiri (pola PreDepartureConfirmLockTest). Dilewati dengan jelas tanpa
 * SPATIAL_TEST_DSN (SpatialTestCase).
 */
class KunciCronTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    public function test_a_held_lock_refuses_a_second_holder_until_it_is_released(): void
    {
        $toko = Cache::store('database');
        $pertama = $toko->lock('cron:data:freshness-check', 300);
        $kedua = $toko->lock('cron:data:freshness-check', 300);

        try {
            $this->assertTrue($pertama->get());
            $this->assertFalse($kedua->get(), 'Pemegang kedua mendapat kunci yang masih dipegang.');

            $pertama->release();

            $this->assertTrue($kedua->get(), 'Kunci yang sudah dilepas tidak dapat diambil lagi.');
        } finally {
            DB::table('cache_locks')->delete();
        }
    }

    /**
     * Jalan yang mati karena batas waktu tidak melepas kuncinya. Begitu kedaluwarsa, jalan berikutnya mengambil alih.
     */
    public function test_an_expired_lock_is_taken_over_by_the_next_run(): void
    {
        $toko = Cache::store('database');

        try {
            $this->assertTrue($toko->lock('cron:weather:refresh', 300)->get());

            DB::table('cache_locks')->update(['expiration' => now()->subSecond()->getTimestamp()]);

            $this->assertTrue($toko->lock('cron:weather:refresh', 300)->get(), 'Kunci kedaluwarsa tidak diambil alih.');
        } finally {
            DB::table('cache_locks')->delete();
        }
    }
}
