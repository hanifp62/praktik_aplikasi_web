<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Sakelar perekaman jejak.
 *
 * Perekaman default mati dan hanya menyala atas keputusan pendaki. Halaman ini tempat
 * keputusan itu diambil, dan juga tempat ia dapat ditarik kembali.
 *
 * Yang dikunci di sini bukan sekadar nilainya tersimpan, melainkan bahwa pendaki diberi
 * tahu apa yang ia setujui sebelum menyalakannya.
 */
class TrackPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function pendaki(bool $merekam = false): User
    {
        $user = User::factory()->create();
        $user->preference()->create([
            'preferred_duration' => 'ONE_DAY',
            'record_track' => $merekam,
        ]);

        return $user->fresh();
    }

    /**
     * Kabar sesudah simpan diubah sisipan W6-P2 dengan alasan (rancangan W6-P2 bagian 4): kalimat
     * lama "untuk pendakian berikutnya" salah saat pendakian berlangsung, karena mode lapangan
     * yang dibuka ulang ikut merekam pendakian itu.
     */
    public function test_recording_starts_off_and_can_be_turned_on(): void
    {
        $user = $this->pendaki();

        $this->actingAs($user)->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rekamJejak', false));

        $this->actingAs($user)->put(route('profile.preference'), ['record_track' => true])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status-jejak', 'Perekaman jejak dinyalakan untuk mode lapangan yang dibuka dengan sinyal sesudah ini.');

        $this->assertTrue($user->fresh()->preference->record_track);
    }

    /**
     * Diubah sisipan W6-P2 dengan alasan (ruling W6-P2f): titik yang belum terkirim ditahan, tidak
     * dihapus, dan server tidak menerimanya selama perekaman mati. Kalimatnya tidak mengklaim
     * perekaman berhenti di tab atau perangkat lain.
     */
    public function test_it_can_be_turned_back_off(): void
    {
        $user = $this->pendaki(merekam: true);

        $this->actingAs($user)->put(route('profile.preference'), ['record_track' => false])
            ->assertSessionHas('status-jejak', 'Perekaman jejak dimatikan untuk mode lapangan yang dibuka dengan sinyal sesudah ini. Titik yang belum terkirim tetap di perangkat dan tidak diterima Memount selama perekaman mati; jejak yang sudah tersimpan di Memount tidak ikut terhapus.');

        $this->assertFalse($user->fresh()->preference->record_track);
    }

    /**
     * Pendaki yang belum pernah mengisi preferensi sama sekali tetap dapat menyalakan
     * perekaman, dan barisnya dibuat saat itu juga.
     */
    public function test_a_hiker_without_any_preferences_yet_can_still_decide(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.preference'), ['record_track' => true])
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->preference->record_track);
    }

    public function test_a_value_that_is_not_a_yes_or_no_is_refused(): void
    {
        $user = $this->pendaki();

        $this->actingAs($user)->put(route('profile.preference'), ['record_track' => 'kadang'])
            ->assertSessionHasErrors('record_track');

        $this->assertFalse($user->fresh()->preference->record_track);
    }

    /**
     * Persetujuan tanpa keterangan bukan persetujuan. Halaman ini harus menyebut apa
     * yang direkam, kapan dikirim, bahwa jejaknya dapat dihapus, dan biayanya pada
     * baterai, sebelum pendaki menyalakannya. Kalimatnya dirender klien, jadi yang
     * dijaga adalah sumber komponennya.
     */
    public function test_the_hiker_is_told_what_they_are_agreeing_to(): void
    {
        $sumber = File::get(resource_path('js/components/akun/FormRekamJejak.tsx'));

        foreach (['hanya di perangkat Anda', 'setelah Anda turun', 'dapat dihapus', 'memakai baterai lebih cepat'] as $kalimat) {
            $this->assertStringContainsString($kalimat, $sumber);
        }

        // Ruling W6-P2l: dua kalimat janji dijaga utuh, karena perilaku kiriman (ruling W6-P2)
        // mengikuti kalimat ini, bukan sebaliknya. Mengubahnya butuh izin pemilik.
        $this->assertStringContainsString('<span>Selama mendaki, titik-titiknya disimpan <strong>hanya di perangkat Anda</strong>. Tidak ada yang dikirim saat Anda masih di jalur.</span>', $sumber);
        $this->assertStringContainsString('<span>Jejaknya dikirim <strong>setelah Anda turun</strong> dan mendapat sinyal kembali.</span>', $sumber);

        $this->assertStringContainsString('<FormRekamJejak', File::get(resource_path('js/pages/Profile/Edit.tsx')));
    }

    /**
     * Sesudah tersimpan (ruling W6-P2f, W6-9h): setiap simpan melupakan keputusan pengirim jejak
     * yang diingat di tab ini, dan hanya simpan "mati" yang mencabut izin rekam jejak di rekaman
     * sesi paket luring perangkat ini. Nilainya diambil saat formulir dikirim, dan pencabutan
     * hanya terjadi sesudah server menyimpan (onSuccess), bukan sebelum.
     */
    public function test_saving_the_switch_revokes_the_shell_permission_only_when_turned_off(): void
    {
        $sumber = File::get(resource_path('js/components/akun/FormRekamJejak.tsx'));
        $kirim = substr($sumber, (int) strpos($sumber, 'const kirim = (e: FormEvent) => {'));

        $this->assertStringContainsString("const mati = !form.data.record_track;\n\n        form.put(preference.url(), {", $kirim);
        $this->assertStringContainsString(
            "onSuccess: () => {\n                lupakanKeputusanJejak();\n\n                if (mati && kunci !== null) {\n                    void cabutRekamJejakSesi(kunci).catch(() => undefined);\n                }\n            },",
            $kirim,
        );
        $this->assertSame(1, substr_count($sumber, 'cabutRekamJejakSesi('), 'Satu-satunya pemanggil ada di cabang mati sesudah tersimpan.');
    }

    /**
     * Yang diubah selalu preferensi pengguna yang sedang masuk. Id pengguna lain yang
     * disisipkan ke payload tidak dipakai.
     */
    public function test_it_never_touches_another_hikers_preference(): void
    {
        $saya = $this->pendaki();
        $orangLain = $this->pendaki();

        $this->actingAs($saya)->put(route('profile.preference'), ['record_track' => true, 'user_id' => $orangLain->id]);

        $this->assertTrue($saya->fresh()->preference->record_track);
        $this->assertFalse($orangLain->fresh()->preference->record_track);
    }
}
