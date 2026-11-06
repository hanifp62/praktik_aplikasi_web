<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * Ruling FF-4 (uji E2E akhir F-4): rute PWA `pwa.sw`, `pwa.manifest`, dan `pwa.offline` berjalan tanpa sesi.
 *
 * Peramban dan service worker mengambil ketiganya di latar: pemeriksaan pembaruan `/sw.js` sesudah setiap muat
 * penuh (`resources/js/dasar.js`), manifest dari `<link rel="manifest">`, dan `/offline` lewat
 * `cache.addAll(SHELL_FILES)` saat service worker baru dipasang. Selama rute itu menjalankan StartSession,
 * setiap permintaan yang membawa cookie sesi menuakan flash. POST yang mem-flash `status` sudah menuakannya sekali
 * saat sesinya disimpan, jadi SATU permintaan latar di antara POST itu dan GET sesudah redirect sudah menghapusnya,
 * dan kabar "Hasil cek disimpan" tidak tampil. Permintaan latar yang memuat sesi sebelum POST selesai lalu
 * menyimpannya sesudahnya juga dapat menimpa flash itu.
 *
 * Yang dilepas hanya middleware grup `web` yang membutuhkan sesi: StartSession, ShareErrorsFromSession (membaca
 * `$request->session()`), dan PreventRequestForgery (menulis cookie XSRF-TOKEN dari token sesi). Yang lain tetap:
 * SecurityHeaders, HandleInertiaRequests (header `Vary`), EncryptCookies, AddQueuedCookiesToResponse, dan
 * SubstituteBindings.
 */
class PwaRoutesWithoutSessionTest extends TestCase
{
    use RefreshDatabase;

    private const RUTE_PWA = ['/sw.js', '/manifest.webmanifest', '/offline'];

    private const KABAR = 'Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini.';

    /**
     * Mekanisme F-4 diulang persis: POST yang mem-flash `status`, lalu ketiga permintaan latar dengan sesi yang
     * sama, lalu GET halaman Inertia sesudah redirect. Sebelum ruling FF-4 `flash.status` di GET itu null.
     */
    public function test_a_flash_message_survives_the_background_pwa_requests(): void
    {
        $trip = $this->trip();
        $this->actingAs($trip->user);

        $this->post(route('trips.readiness.recompute', $trip))
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('status', self::KABAR);

        foreach (self::RUTE_PWA as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('trips.readiness', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Readiness')
                ->where('flash.status', self::KABAR));
    }

    /**
     * Tidak satu pun dari ketiga respons menulis cookie sesi atau cookie XSRF-TOKEN (yang dibentuk dari token sesi),
     * baik untuk tamu maupun pengguna yang masuk.
     */
    public function test_the_pwa_responses_write_no_session_cookie(): void
    {
        foreach ([null, User::factory()->create()] as $pengguna) {
            if ($pengguna !== null) {
                $this->actingAs($pengguna);
            }

            foreach (self::RUTE_PWA as $url) {
                $cookie = array_map(fn (Cookie $c) => $c->getName(), $this->get($url)->assertOk()->headers->getCookies());

                $this->assertNotContains(config('session.cookie'), $cookie, "{$url} menulis cookie sesi.");
                $this->assertNotContains('XSRF-TOKEN', $cookie, "{$url} menulis cookie XSRF-TOKEN.");
            }
        }
    }

    /**
     * Pengguna "ingat saya" membawa cookie recaller tanpa sesi yang dimuat. HandleInertiaRequests tetap memanggil
     * `$request->user()`, dan SessionGuard memulihkan pengguna dari recaller tanpa StartSession: rutenya tetap 200 dan
     * tidak menulis cookie apa pun.
     */
    public function test_a_remembered_user_gets_the_pwa_routes_without_a_new_cookie(): void
    {
        $pengguna = User::factory()->create(['remember_token' => 'token-ingat-saya']);
        $recaller = Auth::guard('web')->getRecallerName();

        foreach (self::RUTE_PWA as $url) {
            $respons = $this->withCookie($recaller, $pengguna->id.'|token-ingat-saya|'.$pengguna->getAuthPassword())->get($url)->assertOk();

            $this->assertSame([], $respons->headers->getCookies(), "{$url} menulis cookie.");
        }

        $this->assertAuthenticatedAs($pengguna);
        $this->assertTrue(Auth::guard('web')->viaRemember(), 'Jalur recaller tidak teruji: pengguna tidak dipulihkan dari cookie ingat saya.');
    }

    /**
     * Isi dan header ketiga rute tidak berubah: Content-Type, Service-Worker-Allowed, dan Cache-Control milik
     * controller, header keamanan dari SecurityHeaders, dan `Vary: X-Inertia` dari HandleInertiaRequests, dua
     * middleware yang tidak membutuhkan sesi dan karena itu tetap berjalan.
     */
    public function test_the_pwa_routes_keep_their_content_and_headers(): void
    {
        $sw = $this->get('/sw.js')->assertOk()
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertHeader('Service-Worker-Allowed', '/');
        $this->assertStringContainsString('no-cache', $sw->headers->get('Cache-Control'));
        $this->assertSame(view('sw')->render(), $sw->getContent());

        $this->get('/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('start_url', '/dashboard');

        $this->get('/offline')->assertOk()->assertSee('Halaman ini belum dapat dimuat');

        foreach (self::RUTE_PWA as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Vary', 'X-Inertia');
            $this->assertStringContainsString("frame-ancestors 'none'", $this->get($url)->headers->get('Content-Security-Policy'));
        }
    }

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->published()->for(Mountain::factory())->easy()->create()->id,
            'name' => 'Uji kabar',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update(['status' => PreparationStatus::CONFIRMED->value, 'status_updated_at' => now()]);

        return $trip->fresh();
    }
}
