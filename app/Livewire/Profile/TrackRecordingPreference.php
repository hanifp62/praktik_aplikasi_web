<?php

namespace App\Livewire\Profile;

use Livewire\Component;

/**
 * Sakelar perekaman jejak pendakian.
 *
 * Perekaman default mati dan hanya menyala atas keputusan pendaki. Persetujuan tanpa
 * keterangan bukan persetujuan, jadi halaman ini menyebut apa yang direkam, di mana ia
 * disimpan, kapan dikirim, dan bahwa ia dapat dihapus, sebelum sakelarnya disentuh.
 */
class TrackRecordingPreference extends Component
{
    public bool $record_track = false;

    public function mount(): void
    {
        $this->record_track = (bool) auth()->user()->preference?->record_track;
    }

    public function simpan(): void
    {
        $user = auth()->user();

        // Pendaki yang belum pernah mengisi preferensi sama sekali tetap dapat
        // memutuskan, dan barisnya dibuat saat itu juga.
        $user->preference()->updateOrCreate(
            ['user_id' => $user->id],
            ['record_track' => $this->record_track]
        );

        session()->flash('status', $this->record_track
            ? 'Perekaman jejak dinyalakan untuk pendakian berikutnya.'
            : 'Perekaman jejak dimatikan. Jejak yang sudah tersimpan tidak ikut terhapus.');
    }

    public function render()
    {
        return view('livewire.profile.track-recording-preference');
    }
}
