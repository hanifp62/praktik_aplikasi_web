<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * Kunjungan Inertia yang berakhir galat (ruling W4-6j, dititipkan ke W9).
 *
 * Sebelum W9, setiap kunjungan Inertia yang dijawab 403, 404, 419, 429, 500, atau 503
 * menampilkan dialog pengembang Inertia: halaman HTML galat di dalam iframe, dan untuk 429
 * halaman bawaan Laravel berbahasa Inggris karena aplikasi tidak punya 429.blade.php.
 * Kini kunjungan itu mendapat halaman React Galat dengan isi yang sama persis dengan
 * halaman Blade untuk muat penuh (App\Support\HalamanGalat), dan halaman itu tidak membaca
 * props bersama yang menyentuh basis data, karena ia dirender justru ketika sesuatu rusak.
 */
class InertiaErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_inertia_visit_to_a_missing_page_gets_the_indonesian_error_page_instead_of_the_dialog(): void
    {
        $respons = $this->kunjunganInertia('GET', '/jalur-yang-tidak-pernah-ada');

        $respons->assertStatus(404)->assertHeader('X-Inertia', 'true');
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame(404, $respons->json('props.galat.kode'));
        $this->assertSame('Halaman ini tidak ditemukan', $respons->json('props.galat.judul'));
        $this->assertSame('Halaman ini tidak ditemukan', $respons->json('props.title'));
    }

    public function test_another_hikers_trip_answers_an_inertia_visit_with_the_refusal_page(): void
    {
        $trip = TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Trip orang lain',
            'planned_date' => now()->addDays(4)->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'PLANNED',
        ]);

        $respons = $this->actingAs(User::factory()->create())->kunjunganInertia('GET', '/trips/'.$trip->id);

        $respons->assertStatus(403);
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame('Anda tidak punya akses ke halaman ini', $respons->json('props.galat.judul'));
        $this->assertStringNotContainsString('Trip orang lain', $respons->getContent(), 'Halaman penolakan tidak membawa isi trip yang ditolak.');
    }

    /**
     * Halaman galat dirender justru ketika sesuatu rusak. Props bersama kerangka membaca basis
     * data (hitungan Simpanan, pendakian aktif, peran kurator), jadi halaman ini hanya membawa
     * yang dibacanya sendiri: judul, isi galat, merek, penanda data contoh, tema, dan tampilan.
     */
    public function test_the_error_page_carries_only_props_that_never_touch_the_database(): void
    {
        $trip = TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Trip orang lain',
            'planned_date' => now()->addDays(4)->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'PLANNED',
        ]);

        $props = $this->actingAs(User::factory()->create())
            ->kunjunganInertia('GET', '/trips/'.$trip->id)
            ->json('props');

        $kunci = array_keys($props);
        sort($kunci);

        $this->assertSame(['demo', 'galat', 'merek', 'tampilan', 'tema', 'title'], $kunci);
    }

    /**
     * Batas laju untuk aksi (POST) tidak memindah alamat ke rute aksi itu: alamat halaman
     * galat adalah halaman asal (Referer), jadi "Muat ulang halaman" dan tombol Kembali
     * peramban membuka halaman yang memang dapat dibuka dengan GET.
     */
    public function test_a_throttled_inertia_action_says_how_long_to_wait_and_keeps_the_page_it_came_from(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->post('/_uji/aksi-terbatas', fn () => back());

        $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => url('/trips?saring=semua')])->assertRedirect();
        $respons = $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => url('/trips?saring=semua')]);

        $respons->assertStatus(429);
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame('Terlalu banyak permintaan dalam waktu singkat', $respons->json('props.galat.judul'));
        $this->assertSame('/trips?saring=semua', $respons->json('url'));
        $this->assertMatchesRegularExpression('/Tunggu sekitar \d+ detik, lalu muat ulang halaman\./', implode(' ', $respons->json('props.galat.penjelasan')));
        $this->assertSame([['label' => 'Muat ulang halaman', 'href' => '/trips?saring=semua', 'utama' => true]], $respons->json('props.galat.tindakan'));
    }

    public function test_a_referer_from_another_site_never_becomes_the_page_address(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->post('/_uji/aksi-terbatas', fn () => back());

        $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => 'https://situs-lain.test/jebakan']);
        $respons = $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => 'https://situs-lain.test/jebakan']);

        $respons->assertStatus(429);
        $this->assertSame('/', $respons->json('url'));
        $this->assertSame('/', $respons->json('props.galat.tindakan.0.href'));
    }

    public function test_an_expired_session_on_an_inertia_action_offers_to_sign_in_again(): void
    {
        Route::middleware('web')->post('/_uji/sesi-berakhir', fn () => abort(419));

        $respons = $this->kunjunganInertia('POST', '/_uji/sesi-berakhir', ['Referer' => url('/trips')]);

        $respons->assertStatus(419);
        $this->assertSame('Sesi Anda sudah berakhir', $respons->json('props.galat.judul'));
        $this->assertSame(route('login', absolute: false), parse_url((string) $respons->json('props.galat.tindakan.0.href'), PHP_URL_PATH));
        $this->assertSame('/trips', $respons->json('url'));
    }

    /**
     * Rincian galat server tidak pernah sampai ke pendaki. Dengan APP_DEBUG menyala,
     * pengembang tetap mendapat halaman debug di dialog Inertia seperti sebelumnya.
     */
    public function test_a_server_error_hides_its_details_from_hikers_but_not_from_developers(): void
    {
        Route::middleware('web')->get('/_uji/meledak', fn () => throw new RuntimeException('rahasia-internal-basis-data'));

        config(['app.debug' => false]);
        $respons = $this->kunjunganInertia('GET', '/_uji/meledak');

        $respons->assertStatus(500);
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame('Ada yang bermasalah di sisi kami', $respons->json('props.galat.judul'));
        $this->assertStringNotContainsString('rahasia-internal-basis-data', $respons->getContent());

        config(['app.debug' => true]);
        $debug = $this->kunjunganInertia('GET', '/_uji/meledak');

        $debug->assertStatus(500)->assertHeaderMissing('X-Inertia');
    }

    public function test_a_validation_error_still_returns_to_the_form(): void
    {
        $respons = $this->actingAs(User::factory()->create())->kunjunganInertia('POST', route('trips.store'), ['Referer' => url('/trips/create')]);

        $respons->assertRedirect('/trips/create');
        $respons->assertSessionHasErrors('trail_id');
    }

    public function test_a_full_page_load_and_an_inertia_visit_read_the_same_words(): void
    {
        $blade = $this->get('/jalur-yang-tidak-pernah-ada')->assertStatus(404)->getContent();
        $inertia = $this->kunjunganInertia('GET', '/jalur-yang-tidak-pernah-ada');

        foreach ([$inertia->json('props.galat.judul'), ...$inertia->json('props.galat.penjelasan')] as $kalimat) {
            $this->assertStringContainsString(e($kalimat), $blade);
        }
    }

    /**
     * Kunjungan Inertia dengan kepala yang sama dengan klien @inertiajs/core 3.7: GET membawa
     * versi aset server, karena versi yang berbeda dijawab 409 sebelum halaman apa pun
     * dirender, dan Accept HTML membuat Laravel tidak menjawab galat sebagai JSON.
     *
     * @param  array<string, string>  $kepala
     */
    private function kunjunganInertia(string $metode, string $alamat, array $kepala = []): TestResponse
    {
        $permintaan = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
            ...$kepala,
        ]);

        return $metode === 'GET' ? $permintaan->get($alamat) : $permintaan->post($alamat);
    }
}
