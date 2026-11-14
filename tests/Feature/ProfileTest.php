<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

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
     * Ringkasan pengalaman memakai label dari enum, dan isian yang belum diisi dikirim null,
     * bukan ditebak (R-007).
     */
    public function test_the_hiking_profile_summary_shows_labels_and_leaves_blanks_blank(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        $this->actingAs($user)->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pengalaman.lengkap', true)
                ->where('pengalaman.rincian.0', ['label' => 'Tingkat', 'nilai' => 'Menengah'])
                ->where('pengalaman.rincian.2', ['label' => 'Tanjakan tertinggi', 'nilai' => null])
                ->where('preferensi.rincian.1', ['label' => 'Lama pendakian', 'nilai' => null]));
    }

    /**
     * Hub Profil (spec v3 bagian 6.15, prototype 14): identitas dengan tingkat yang diisi
     * sendiri, pengalaman, preferensi termasuk wilayah minat, dan perlengkapan urut nama;
     * "Ubah" menuju langkah onboarding yang bersangkutan.
     */
    public function test_the_profile_hub_reads_the_hikers_own_answers(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8));
        $user = User::factory()->create(['name' => 'Budi Setiawan', 'created_at' => '2026-02-10 03:00:00']);
        $user->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 4, 'terrain_experience' => ['FOREST', 'ROCKY'], 'navigation_experience' => 'BASIC', 'highest_elevation_gain_m' => 1660]);
        $user->preference()->create(['preferred_duration' => 'TWO_DAY', 'preferred_trip_type' => 'CAMPING', 'interest_regions' => ['JAWA_TENGAH_DIY', 'JAWA_BARAT_BANTEN']]);
        $user->ownedItems()->createMany([['name' => 'Jas hujan'], ['name' => 'Headlamp']]);

        $this->actingAs($user)->get('/profile')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('identitas.nama', 'Budi Setiawan')
            ->where('identitas.tingkat', 'Pemula, diisi sendiri')
            ->where('identitas.bergabung', 'Bergabung Feb 2026')
            ->where('identitas.foto', null)
            ->where('pengalaman.rincian.1.nilai', '4')
            ->where('pengalaman.rincian.2.nilai', '1.660 m')
            ->where('pengalaman.rincian.3.nilai', 'Hutan, Berbatu')
            ->where('pengalaman.rincian.4.nilai', 'Dasar')
            ->where('pengalaman.ubah', route('onboarding'))
            ->where('preferensi.rincian.0.nilai', 'Camping')
            ->where('preferensi.rincian.1.nilai', '2 hari')
            ->where('preferensi.ubah', route('onboarding', ['langkah' => 3]))
            ->where('perlengkapan.daftar', [['id' => $user->ownedItems()->where('name', 'Headlamp')->value('id'), 'nama' => 'Headlamp'], ['id' => $user->ownedItems()->where('name', 'Jas hujan')->value('id'), 'nama' => 'Jas hujan']])
            ->where('perlengkapan.batas', 40)
            ->where('kabar', null));
    }

    /**
     * Pilihan bahasa disembunyikan sampai terjemahan Inggris ada (DK-40), dan tidak ada
     * tautan kebijakan ke halaman yang belum ada.
     */
    public function test_the_profile_offers_no_control_that_does_not_work_yet(): void
    {
        $sumber = collect([resource_path('js/pages/Profile/Edit.tsx'), ...glob(resource_path('js/components/profil/*.tsx'))])
            ->map(fn (string $berkas) => file_get_contents($berkas))
            ->implode("\n");

        foreach (['English', 'Bahasa', 'Kebijakan data pribadi'] as $kata) {
            $this->assertStringNotContainsString($kata, $sumber);
        }
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

    /**
     * Ruling AF-5 (audit E2E I-5): sebelum aksi yang tidak dapat diurungkan, kartu Hapus Akun tidak
     * menyuruh langkah yang mustahil. Memount tidak punya rute unduh atau ekspor data, jadi kalimat
     * "Unduh dahulu data yang ingin Anda simpan." dibuang dan tidak ada janji ekspor di kartu atau
     * dialognya. Kalimat yang tersisa mengikuti KEBIJAKAN-DATA-PRIBADI bagian 2.
     */
    public function test_the_delete_account_card_does_not_promise_a_data_download(): void
    {
        // Docblock komponen boleh menjelaskan alasannya; yang dijaga adalah teks yang dirender.
        $sumber = $this->tanpaKomentarJs(File::get(resource_path('js/components/akun/HapusAkun.tsx')));

        $this->assertDoesNotMatchRegularExpression('/[Uu]nduh dahulu|[Ee]kspor data|[Uu]nduh data/', $sumber);
        $this->assertStringContainsString(
            'subtitle="Setelah akun dihapus, profil, rencana, dan trip Anda hilang permanen. Laporan kondisi jalur yang sudah disetujui tetap disimpan tanpa nama Anda, karena pendaki lain masih memakainya."',
            $sumber,
        );

        $rute = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri())->implode(' ');
        $this->assertDoesNotMatchRegularExpression('/ekspor|export|unduh-data|download-data/i', $rute, 'Bila rute ekspor data ditambahkan, kalimat kartu ini perlu ditinjau ulang.');
    }

    /**
     * TA-m5 (tinjauan audit): kunci terjemahan Breeze bawaan di lang/id.json tidak dipakai
     * komponen mana pun saat ini (digrep terhadap resources/ dan app/), tetapi masih
     * menyuruh "Unduh dahulu data yang ingin Anda simpan.", padahal Memount tidak punya
     * rute unduh atau ekspor data (ruling AF-5). Kalimat yang mustahil itu dapat muncul
     * lagi begitu kunci ini dipakai, jadi dijaga di sumbernya, bukan hanya di komponen
     * HapusAkun.tsx yang sudah diperbaiki.
     */
    public function test_the_unused_breeze_translation_does_not_promise_a_download(): void
    {
        $isi = File::get(lang_path('id.json'));

        $this->assertStringNotContainsString('Unduh dahulu data yang ingin Anda simpan.', $isi);
    }
}
