<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pameran primitif hanya ada di APP_ENV=local (ruling W1-R5).
 *
 * Halamannya untuk pengembang dan peninjau desain, bukan untuk pendaki. Di luar local
 * rutenya tidak terdaftar sama sekali (404, bukan 403 yang membocorkan keberadaannya),
 * tidak masuk sitemap, dan halamannya meminta mesin pencari tidak mengindeksnya.
 */
class DevPrimitivesRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_showcase_does_not_exist_outside_local(): void
    {
        $this->assertFalse(Route::has('dev.primitif'));

        $this->actingAs(User::factory()->create())->get('/dev/primitif')->assertNotFound();
    }

    public function test_the_routes_file_registers_the_showcase_only_in_local(): void
    {
        $this->assertMatchesRegularExpression(
            "/if \(app\(\)->environment\('local'\)\) \{\s*require __DIR__\.'\/dev\.php';\s*\}/",
            File::get(base_path('routes/web.php'))
        );
    }

    /**
     * Penjaga kedua, di dalam closure rute itu sendiri (ruling W1-8a).
     *
     * Pemuatan bersyarat "if (app()->environment('local'))" di routes/web.php hanya
     * berjalan ketika berkas rute ini benar-benar dibaca ulang saat boot. Bila tabel
     * rute sempat di-cache (`php artisan route:cache`) selagi APP_ENV=local, lalu
     * berkas cache itu dipakai di lingkungan lain, pemeriksaan di web.php tidak pernah
     * berjalan lagi karena boot langsung memuat rute dari cache tanpa mengeksekusi ulang
     * berkas ini, padahal rutenya tetap terdaftar di cache itu. Test ini mensimulasikan
     * kondisi itu secara langsung: rutenya didaftarkan manual walau environment-nya
     * tetap testing (bukan local), dan closure rute sendiri yang harus menolak
     * permintaannya untuk pengguna yang sudah masuk, bukan hanya pemuatan berkas yang
     * menjaganya.
     */
    public function test_the_route_itself_still_refuses_outside_local_even_when_registered(): void
    {
        Route::middleware('web')->group(base_path('routes/dev.php'));

        $this->actingAs(User::factory()->create())->get('/dev/primitif')->assertNotFound();
    }

    /**
     * Rutenya dimuat langsung di sini karena suite berjalan di APP_ENV=testing.
     *
     * Sejak ruling W1-8a rutenya punya penjaga sendiri (abort_unless local) di dalam
     * closure, jadi kondisi "benar-benar local" harus disimulasikan di sini juga, bukan
     * hanya lewat pemuatan berkas rute; tanpa ini permintaannya selalu 404 di suite yang
     * environment-nya testing, walau rutenya sudah terdaftar dan penggunanya sah.
     */
    public function test_the_showcase_renders_its_page_when_its_routes_are_loaded(): void
    {
        $this->app['env'] = 'local';

        Route::middleware('web')->group(base_path('routes/dev.php'));

        $this->actingAs(User::factory()->create())
            ->get('/dev/primitif')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dev/Primitif')->where('title', 'Pameran primitif'));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        Route::middleware('web')->group(base_path('routes/dev.php'));

        $this->get('/dev/primitif')->assertRedirect('/login');
    }

    public function test_the_showcase_is_not_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('dev/primitif');
    }

    public function test_the_showcase_asks_search_engines_not_to_index_it(): void
    {
        $this->assertStringContainsString('<meta name="robots" content="noindex" />', File::get(resource_path('js/pages/Dev/Primitif.tsx')));
    }
}
