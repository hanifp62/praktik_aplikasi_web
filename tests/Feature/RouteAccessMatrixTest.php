<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Menghitung ulang seluruh rute aplikasi dan menanyakan satu hal ke masing-masing:
 * siapa yang boleh membukanya.
 *
 * Daftar putih di bawah ditulis manual. Rute baru yang lupa dijaga tidak akan lolos
 * diam-diam, karena test ini menemukannya sebagai rute publik yang tidak terdaftar,
 * bukan menunggu seseorang ingat memeriksanya.
 */
class RouteAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rute yang memang boleh dibuka tanpa masuk.
     *
     * @var array<int, string>
     */
    private const PUBLIK = [
        '/',
        'login',
        'register',
        'forgot-password',
        'reset-password/{token}',
        'manifest.webmanifest',
        'sw.js',
        'offline',
        'up',

        /*
         * Halaman jalur publik dan sitemap-nya, satu-satunya bagian aplikasi yang
         * sengaja terlihat mesin pencari.
         *
         * Keduanya dibaca robot lebih sering daripada manusia dan isinya sengaja hanya
         * keterangan berumur panjang: tanpa status resmi, tanpa prakiraan cuaca, tanpa
         * data pribadi siapa pun. PublicTrailPageTest yang menjaga batas itu.
         *
         * 'pendakian/{trail:slug}' tidak perlu didaftarkan karena slug contohnya tidak
         * ada dan rutenya menjawab 404, tetapi ditulis di sini supaya pembaca berikutnya
         * tahu ia memang publik dan bukan kelolosan.
         */
        'sitemap.xml',
        'pendakian/{trail:slug}',
    ];

    public function test_every_route_is_either_public_by_design_or_closed_to_guests(): void
    {
        $bocor = [];

        foreach ($this->ruteGet() as $uri) {
            if (in_array($uri, self::PUBLIK, true)) {
                continue;
            }

            $status = $this->get('/'.ltrim($this->isi($uri), '/'))->getStatusCode();

            // 302 ke halaman masuk, 403 ditolak, 404 karena id contoh tidak ada.
            // Yang tidak boleh adalah 200: tamu melihat isinya.
            if ($status === 200) {
                $bocor[] = $uri;
            }
        }

        $this->assertSame([], $bocor, 'Rute berikut terbuka untuk tamu: '.implode(', ', $bocor));
    }

    public function test_an_ordinary_hiker_cannot_reach_any_admin_page(): void
    {
        $this->actingAs(User::factory()->create());

        $bocor = [];

        foreach ($this->ruteGet() as $uri) {
            if (! str_starts_with($uri, 'admin/')) {
                continue;
            }

            if ($this->get('/'.$this->isi($uri))->getStatusCode() === 200) {
                $bocor[] = $uri;
            }
        }

        $this->assertSame([], $bocor, 'Halaman admin terbuka untuk pendaki biasa: '.implode(', ', $bocor));
    }

    public function test_an_ordinary_hiker_cannot_reach_moderation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/moderation')
            ->assertStatus(403);
    }

    public function test_a_moderator_cannot_reach_admin_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::MODERATOR->value]))
            ->get('/admin/trails')
            ->assertStatus(403);
    }

    /**
     * Mengganti parameter rute dengan nilai contoh. Nilainya tidak perlu ada: yang
     * diperiksa adalah siapa yang ditolak, dan penolakan terjadi sebelum pencarian data.
     */
    private function isi(string $uri): string
    {
        return preg_replace('/\{[^}]+\}/', '1', $uri);
    }

    /**
     * @return array<int, string>
     */
    private function ruteGet(): array
    {
        $uri = [];

        foreach (Route::getRoutes() as $rute) {
            if (! in_array('GET', $rute->methods(), true)) {
                continue;
            }

            if (str_starts_with($rute->uri(), '_') || str_starts_with($rute->uri(), 'livewire')) {
                continue;
            }

            $uri[] = $rute->uri();
        }

        return array_values(array_unique($uri));
    }
}
