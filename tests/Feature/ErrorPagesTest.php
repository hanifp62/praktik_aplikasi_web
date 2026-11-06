<?php

namespace Tests\Feature;

use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
