<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/Register')
                ->where('petunjukSandi', 'Minimal 8 karakter.'));
    }

    public function test_new_users_can_register(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('onboarding', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'name' => 'Test User']);
        Event::assertDispatched(Registered::class);
    }

    public function test_an_email_that_is_already_registered_is_refused_with_a_clear_message(): void
    {
        User::factory()->create(['email' => 'rani@contoh.id']);

        $this->post(route('register.store'), [
            'name' => 'Rani Lain',
            'email' => 'rani@contoh.id',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['email' => 'Email sudah dipakai.']);

        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'rani@contoh.id')->count());
    }

    public function test_a_password_shorter_than_the_hint_says_is_refused(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Rani',
            'email' => 'rani@contoh.id',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    /**
     * Pendaftar tidak dapat menetapkan perannya sendiri: kolom di luar tiga isian
     * formulir dibuang sebelum baris dibuat.
     */
    public function test_a_registrant_cannot_make_themselves_an_admin(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@contoh.id',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::ADMIN->value,
        ])->assertSessionHasNoErrors();

        $this->assertFalse(User::where('email', 'penyusup@contoh.id')->firstOrFail()->isAdmin());
    }
}
