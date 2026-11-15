<?php

namespace Tests\Feature;

use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Aplikasi tidak punya satu pun halaman galat sendiri, sehingga setiap kegagalan
 * dijawab halaman bawaan Laravel berbahasa Inggris berisi satu kata: "Not Found".
 *
 * Yang paling sering ditemui produk ini adalah 419. Pendaki membuka aplikasi di rumah,
 * menutup layar, lalu membukanya lagi berjam-jam kemudian di basecamp. Sesinya sudah
 * berakhir, dan yang ia dapat adalah "Page Expired" tanpa satu pun jalan keluar.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function kodeGalat(): array
    {
        return [
            '404' => ['404'],
            '403' => ['403'],
            '419' => ['419'],
            '429' => ['429'],
            '500' => ['500'],
            '503' => ['503'],
        ];
    }

    #[DataProvider('kodeGalat')]
    public function test_the_page_speaks_indonesian_and_offers_a_way_out(string $kode): void
    {
        $isi = view('errors.'.$kode, ['exception' => null])->render();

        $this->assertSame(1, substr_count($isi, '<h1'), 'Satu h1 agar strukturnya terbaca pembaca layar.');
        $this->assertMatchesRegularExpression('/href="[^"]+"/', $isi, 'Halaman galat tanpa tautan adalah jalan buntu.');

        foreach (['Not Found', 'Forbidden', 'Page Expired', 'Server Error', 'Service Unavailable'] as $inggris) {
            $this->assertStringNotContainsString($inggris, $isi);
        }
    }

    public function test_a_missing_page_is_answered_in_indonesian(): void
    {
        $this->get('/jalur-yang-tidak-pernah-ada')
            ->assertStatus(404)
            ->assertSee('Halaman ini tidak ditemukan');
    }

    /**
     * Trip milik orang lain harus ditolak, dan penolakannya harus terbaca sebagai
     * penolakan, bukan sebagai kerusakan.
     */
    public function test_another_users_trip_is_refused_in_indonesian(): void
    {
        $trip = TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Trip orang lain',
            'planned_date' => now()->addDays(4)->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'PLANNED',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/trips/'.$trip->id)
            ->assertStatus(403)
            ->assertSee('tidak punya akses');
    }

    /**
     * Sesi yang berakhir bukan kesalahan pengguna, jadi halamannya harus menjelaskan
     * apa yang terjadi dan memberi satu langkah berikutnya, bukan menyalahkan.
     */
    public function test_the_expired_session_page_explains_itself(): void
    {
        $isi = view('errors.419', ['exception' => null])->render();

        $this->assertStringContainsString('Sesi Anda sudah berakhir', $isi);
        $this->assertStringContainsString(route('login'), $isi);
    }

    /**
     * Halaman galat dirender justru ketika sesuatu sedang rusak, jadi ia tidak boleh
     * bergantung pada basis data, sesi, atau komponen Livewire.
     */
    #[DataProvider('kodeGalat')]
    public function test_the_page_does_not_depend_on_anything_that_may_be_broken(string $kode): void
    {
        $sumber = file_get_contents(resource_path('views/errors/'.$kode.'.blade.php'));

        foreach (['@livewire', 'auth()->user()', 'DB::', '::query()'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $sumber);
        }
    }

    /**
     * Kata-kata keenam halaman kini tinggal di satu kelas (App\Support\HalamanGalat) yang juga
     * dibaca halaman React Galat untuk kunjungan Inertia (W9). Kelas dan layoutnya tunduk pada
     * larangan yang sama: tanpa basis data, tanpa sesi, tanpa pengguna.
     */
    public function test_the_shared_layout_and_its_words_do_not_depend_on_anything_that_may_be_broken(): void
    {
        foreach ([resource_path('views/errors/layout.blade.php'), app_path('Support/HalamanGalat.php')] as $berkas) {
            $sumber = file_get_contents($berkas);

            foreach (['@livewire', 'auth()', 'DB::', '::query()', 'session(', '->user()'] as $terlarang) {
                $this->assertStringNotContainsString($terlarang, $sumber, basename($berkas));
            }
        }
    }

    /**
     * 429 dari batas laju dulu dijawab halaman bawaan Laravel berbahasa Inggris (ruling
     * W4-6j). Halaman ini menyebut berapa lama menunggu dari kepala Retry-After, bukan
     * angka karangan.
     */
    public function test_a_rate_limited_page_load_says_how_long_to_wait(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->get('/_uji/halaman-terbatas', fn () => 'isi');

        $this->get('/_uji/halaman-terbatas')->assertOk();

        $this->get('/_uji/halaman-terbatas')
            ->assertStatus(429)
            ->assertSee('Terlalu banyak permintaan dalam waktu singkat')
            ->assertSeeText('Muat ulang halaman')
            ->assertDontSee('Too Many Requests');

        $this->assertMatchesRegularExpression('/Tunggu sekitar \d+ detik, lalu muat ulang halaman\./', $this->get('/_uji/halaman-terbatas')->getContent());
    }
}
