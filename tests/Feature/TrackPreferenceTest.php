<?php

namespace Tests\Feature;

use App\Livewire\Profile\TrackRecordingPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_recording_starts_off_and_can_be_turned_on(): void
    {
        $user = $this->pendaki();

        Livewire::actingAs($user)
            ->test(TrackRecordingPreference::class)
            ->assertSet('record_track', false)
            ->set('record_track', true)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->preference->record_track);
    }

    public function test_it_can_be_turned_back_off(): void
    {
        $user = $this->pendaki(merekam: true);

        Livewire::actingAs($user)
            ->test(TrackRecordingPreference::class)
            ->set('record_track', false)
            ->call('simpan');

        $this->assertFalse($user->fresh()->preference->record_track);
    }

    /**
     * Pendaki yang belum pernah mengisi preferensi sama sekali tetap dapat menyalakan
     * perekaman, dan barisnya dibuat saat itu juga.
     */
    public function test_a_hiker_without_any_preferences_yet_can_still_decide(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TrackRecordingPreference::class)
            ->set('record_track', true)
            ->call('simpan');

        $this->assertTrue($user->fresh()->preference->record_track);
    }

    /**
     * Persetujuan tanpa keterangan bukan persetujuan. Halaman ini harus menyebut apa
     * yang direkam, kapan dikirim, dan bahwa jejaknya dapat dihapus, sebelum pendaki
     * menyalakannya.
     */
    public function test_the_hiker_is_told_what_they_are_agreeing_to(): void
    {
        $halaman = Livewire::actingAs($this->pendaki())->test(TrackRecordingPreference::class);

        $halaman->assertSee('hanya di perangkat Anda');
        $halaman->assertSee('setelah Anda turun');
        $halaman->assertSee('dapat dihapus');
    }

    public function test_it_never_touches_another_hikers_preference(): void
    {
        $saya = $this->pendaki();
        $orangLain = $this->pendaki();

        Livewire::actingAs($saya)
            ->test(TrackRecordingPreference::class)
            ->set('record_track', true)
            ->call('simpan');

        $this->assertTrue($saya->fresh()->preference->record_track);
        $this->assertFalse($orangLain->fresh()->preference->record_track);
    }
}
