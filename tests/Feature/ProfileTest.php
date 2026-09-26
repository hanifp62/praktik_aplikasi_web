<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Profile/Edit')
                ->where('title', 'Profil Akun')
                ->where('rekamJejak', false)
                ->where('perluVerifikasi', false));
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    /**
     * Ringkasan profil pendakian memakai label dari enum, dan isian yang belum diisi
     * dikirim sebagai null, bukan ditebak (R-007).
     */
    public function test_the_hiking_profile_summary_shows_labels_and_leaves_blanks_blank(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        $this->actingAs($user)->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('profilPendakian.lengkap', true)
                ->where('profilPendakian.rincian.0', ['label' => 'Tingkat pengalaman', 'nilai' => 'Menengah'])
                ->where('profilPendakian.rincian.1', ['label' => 'Preferensi wilayah', 'nilai' => null])
                ->where('profilPendakian.rincian.2', ['label' => 'Durasi yang Anda sukai', 'nilai' => null]));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_the_email_of_another_account_cannot_be_taken(): void
    {
        $orangLain = User::factory()->create(['email' => 'orang-lain@contoh.id']);
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $orangLain->email,
        ])->assertSessionHasErrors(['email' => 'Email sudah dipakai.']);

        $this->assertNotSame($orangLain->email, $user->refresh()->email);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors(['password' => 'Kata sandi salah.'])
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticatedAs($user);
    }
}
