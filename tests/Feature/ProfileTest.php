<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

        // W6-11b: data Memount di perangkat ini ikut dihapus (KEBIJAKAN bagian 4); Clear-Site-Data
        // adalah lapis kedua di samping penghapusan oleh halaman.
        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/')
            ->assertHeader('Clear-Site-Data', '"storage"');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        // Kata sandi salah tidak menghapus apa pun, termasuk data di perangkat (W6-11b).
        $this->actingAs($user)->from('/profile')->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors(['password' => 'Kata sandi salah.'])
            ->assertRedirect('/profile')
            ->assertHeaderMissing('Clear-Site-Data');

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Hapus akun membersihkan data perangkat hanya sesudah server menghapus akunnya (ruling W6-11b,
     * KEBIJAKAN bagian 4): kunci perangkat ditangkap sebelum kiriman, karena sesudah akun terhapus
     * props bersama tidak lagi membawanya, dan pembersihan berjalan di onSuccess, jadi kata sandi
     * yang salah tidak menghapus apa pun. Dialog menyebut bahwa data di perangkat ini ikut dihapus.
     */
    public function test_deleting_the_account_clears_this_devices_data_only_after_the_server_agrees(): void
    {
        $sumber = File::get(resource_path('js/components/akun/HapusAkun.tsx'));
        $kirim = substr($sumber, (int) strpos($sumber, 'const kirim = '));
        $sebelumKirim = substr($kirim, 0, (int) strpos($kirim, 'form.delete('));

        $this->assertStringContainsString('const kunci = perangkat?.kunci ?? null;', $sebelumKirim);
        $this->assertStringNotContainsString('bersihkanPerangkat', $sebelumKirim, 'Data perangkat tidak dihapus sebelum server menjawab.');
        $this->assertStringContainsString('onSuccess: () => void bersihkanPerangkat(kunci, { saatKeluar: true }),', $kirim);
        $this->assertSame(1, substr_count($sumber, 'bersihkanPerangkat('), 'Satu-satunya pemanggilan ada di onSuccess.');
        $this->assertStringContainsString(
            'Setelah akun dihapus, profil, rencana, dan trip Anda hilang permanen dan tidak dapat dikembalikan. Data Memount di perangkat ini, termasuk paket luring dan jejak yang belum terkirim, ikut dihapus. Masukkan kata sandi Anda untuk memastikan.',
            $sumber,
        );
    }
}
