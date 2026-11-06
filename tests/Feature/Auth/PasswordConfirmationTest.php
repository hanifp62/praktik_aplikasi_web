<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create())->get('/confirm-password')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ConfirmPassword'));
    }

    public function test_password_can_be_confirmed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard')
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('password.confirm.store'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors(['password' => 'Kata sandi salah.'])
            ->assertSessionMissing('auth.password_confirmed_at');
    }

    /**
     * Konfirmasi sandi dulu tanpa batas percobaan: sesi yang tertinggal terbuka dapat
     * dipakai menebak sandi pemiliknya tanpa henti.
     */
    public function test_password_confirmation_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $_) {
            $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'salah-'.$_]);
        }

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'salah-lagi'])->assertStatus(429);
    }
}
