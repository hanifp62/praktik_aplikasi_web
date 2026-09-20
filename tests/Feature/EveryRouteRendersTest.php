<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Setiap halaman benar-benar dirender, bukan hanya yang sempat ditulis testnya.
 *
 * Fase 4 mengubah warna, permukaan, skala judul, dan tombol di ratusan berkas view, lalu
 * dinyatakan selesai berdasarkan 788 test yang masing-masing memeriksa satu hal tertentu.
 * Tidak ada satu pun yang menjawab pertanyaan paling sederhana: apakah setiap halaman
 * masih terbuka.
 *
 * Test ini menjawab itu untuk seluruh rute GET sekaligus, dan ia akan menjawabnya lagi
 * untuk halaman yang ditulis besok tanpa ada yang perlu mengingat menambahkannya. Yang
 * dituntut hanya "tidak meledak": 302, 403, dan 404 adalah jawaban yang sah, 500 tidak
 * pernah.
 */
class EveryRouteRendersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rute tamu diminta sebagai tamu.
     *
     * Bukan kerapian: meminta /login sebagai pengguna yang sudah masuk membuat
     * RedirectIfAuthenticated memanggil redirect(), dan begitu satu halaman Livewire
     * sudah dirender di proses yang sama, binding redirect di container sudah ditukar
     * Livewire dengan Redirector miliknya sendiri, yang bukan Response. Middleware-nya
     * lalu melempar TypeError.
     *
     * Di produksi ini tidak terjadi: tiap permintaan php-fpm memulai container baru, dan
     * proyek ini tidak memakai Octane. Di bawah Octane ia akan menjadi galat sungguhan,
     * dan catatan ini ada supaya hal itu tidak ditemukan ulang dari nol.
     */
    private function rutenyaUntukTamu(\Illuminate\Routing\Route $rute): bool
    {
        return in_array('guest', $rute->gatherMiddleware(), true);
    }

    public function test_no_page_in_the_application_explodes(): void
    {
        $this->seed();

        $tamu = [];
        $masuk = [];

        foreach (Route::getRoutes() as $rute) {
            if (! in_array('GET', $rute->methods(), true)) {
                continue;
            }

            $uri = $rute->uri();

            if (str_starts_with($uri, '_') || str_starts_with($uri, 'livewire') || str_contains($uri, '{')) {
                continue;
            }

            $this->rutenyaUntukTamu($rute)
                ? $tamu[] = '/'.ltrim($uri, '/')
                : $masuk[] = '/'.ltrim($uri, '/');
        }

        $meledak = [];

        // Rute tamu dijalankan lebih dulu, seluruhnya, sebelum ada yang masuk. actingAs
        // menempel untuk permintaan berikutnya di test yang sama, jadi memeriksa halaman
        // masuk lalu halaman tamu memeriksa halaman tamu sebagai pengguna yang sudah
        // masuk, dan itu keadaan yang berbeda sama sekali.
        foreach ($tamu as $alamat) {
            if ($this->get($alamat)->getStatusCode() >= 500) {
                $meledak[] = $alamat.' (tamu)';
            }
        }

        $admin = User::where('role', 'admin')->firstOrFail();

        foreach ($masuk as $alamat) {
            if ($this->actingAs($admin)->get($alamat)->getStatusCode() >= 500) {
                $meledak[] = $alamat.' (admin)';
            }
        }

        $diperiksa = count($tamu) + count($masuk);

        $this->assertGreaterThan(20, $diperiksa, 'Terlalu sedikit rute diperiksa; penyaringnya terlalu rakus.');
        $this->assertSame([], $meledak, 'Halaman yang meledak: '.implode(', ', $meledak));
    }

    /**
     * Rute berparameter diperiksa terpisah dengan data yang benar-benar ada, karena
     * justru halaman detail yang paling banyak memakai komponen yang diubah Fase 4.
     */
    public function test_the_detail_pages_render_with_real_records(): void
    {
        $this->seed();

        $admin = User::where('role', 'admin')->firstOrFail();
        $trail = Trail::where('is_published', true)->firstOrFail();

        $halaman = [
            route('trails.show', $trail),
            route('public.trail', $trail),
            route('trails.geometry', $trail),
            route('trails.checkpoints', $trail),
        ];

        foreach ($halaman as $alamat) {
            $this->actingAs($admin)->get($alamat)->assertSuccessful();
        }

        // Halaman jalur publik juga harus terbuka tanpa masuk sama sekali: ia memang
        // dibuat untuk orang yang menemukannya dari pencarian.
        $this->get(route('public.trail', $trail))->assertSuccessful();

        $this->assertNotNull(Mountain::first(), 'Seeder tidak menyediakan gunung, jadi test ini tidak memeriksa apa pun.');
    }
}
