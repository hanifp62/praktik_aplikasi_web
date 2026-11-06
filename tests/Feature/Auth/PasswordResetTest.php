<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from('/forgot-password')->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', 'Tautan atur ulang kata sandi sudah dikirim ke email Anda.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $this->get('/reset-password/token-contoh?email=rani@contoh.id')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'token-contoh')
                ->where('email', 'rani@contoh.id'));
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'kata-sandi-baru',
                'password_confirmation' => 'kata-sandi-baru',
            ])->assertSessionHasNoErrors()->assertRedirect('/login');

            return true;
        });

        $this->assertTrue(Hash::check('kata-sandi-baru', $user->fresh()->password));
    }

    public function test_an_invalid_token_is_refused_and_the_password_stays(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.store'), [
            'token' => 'token-palsu',
            'email' => $user->email,
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])->assertSessionHasErrors(['email' => 'Token atur ulang kata sandi ini tidak berlaku.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
