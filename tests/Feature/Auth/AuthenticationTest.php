<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login')->where('title', 'Masuk'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Tujuan sesudah masuk bisa rute yang bukan halaman Inertia (foto laporan). Kunjungan
     * Inertia yang mengikuti redirect ke rute seperti itu berakhir sebagai galat, jadi
     * permintaan Inertia dijawab dengan muat penuh ke tujuan yang tadi ditolak.
     */
    public function test_an_inertia_login_opens_the_intended_page_with_a_full_load(): void
    {
        $user = User::factory()->create();

        $this->get(route('trips.index'))->assertRedirect(route('login'));

        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertStatus(409)->assertHeader('X-Inertia-Location', route('trips.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak cocok.']);

        $this->assertGuest();
    }

    /**
     * Email yang tidak terdaftar dijawab dengan kalimat yang sama dengan kata sandi
     * salah, supaya formulir masuk tidak dapat dipakai menebak akun mana yang ada.
     */
    public function test_an_unknown_email_gets_the_same_answer_as_a_wrong_password(): void
    {
        $this->post(route('login.store'), ['email' => 'tidak-ada@contoh.id', 'password' => 'apa-saja'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak cocok.']);

        $this->assertGuest();
    }

    public function test_the_sixth_attempt_is_locked_out_even_with_the_right_password(): void
    {
        Event::fake([Lockout::class]);
        $user = User::factory()->create();

        foreach (range(1, 5) as $percobaan) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Terlalu banyak percobaan masuk.', session('errors')->first('email'));
        $this->assertGuest();
        Event::assertDispatched(Lockout::class);
    }

    /**
     * Navigasi React: identitas yang dibutuhkan menu dikirim server, dan kelima tab
     * pemilik (DK-25, PRD §145) tertulis di sumber navigasi yang dirender klien, di luar
     * jangkauan HTTP. Urutan tab dan aturan tab aktif dijaga NavigationConsistencyTest.
     */
    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name));

        preg_match_all("/label: '([^']+)'/", File::get(resource_path('js/layouts/NavLinks.tsx')), $cocok);
        $this->assertSame(['Beranda', 'Jelajah', 'Trip', 'Mendaki', 'Progres'], $cocok[1]);
    }

    public function test_users_can_logout(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_a_guest_cannot_call_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }
}
