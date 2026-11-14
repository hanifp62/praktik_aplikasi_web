<?php

namespace App\Services\Profil;

use App\Enums\InterestRegion;
use App\Enums\TerrainCharacter;
use App\Models\User;
use App\Models\UserOwnedItem;
use App\Support\Timezone;

/**
 * Kelas halaman Profil (spec v3 bagian 6.15; prototype 14; rencana induk W7 butir 5): identitas
 * dengan foto atau inisial, pengalaman dan preferensi yang diisi sendiri dengan jalan "Ubah" ke
 * langkah onboarding yang bersangkutan, dan perlengkapan pribadi. Nilai yang belum diisi dikirim
 * null dan tampil "Belum diisi", bukan ditebak (R-007). Tingkat disebut "diisi sendiri": ia tidak
 * naik karena riwayat (BR-11).
 *
 * Kueri tetap: profil, pengalaman, dan preferensi dimuat sekali; perlengkapan satu kueri.
 */
final class HalamanProfil
{
    /**
     * @param  array{bagian: string, teks: string}|null  $kabar
     * @return array<string, mixed>
     */
    public function untuk(User $user, ?array $kabar): array
    {
        $user->loadMissing('profile', 'experience', 'preference');
        $profil = $user->profile;
        $pengalaman = $user->experience;
        $preferensi = $user->preference;
        $lengkap = $user->hasCompletedProfile();

        $medan = collect($pengalaman?->terrain_experience ?? [])
            ->map(fn (mixed $nilai) => is_string($nilai) ? TerrainCharacter::tryFrom($nilai)?->label() : null)
            ->filter()
            ->implode(', ');
        $wilayah = implode(', ', array_map(fn (InterestRegion $w) => $w->label(), InterestRegion::dari($preferensi?->interest_regions)));
        $tertinggi = $pengalaman?->highest_elevation_gain_m;

        return [
            'identitas' => [
                'nama' => $user->name,
                'tingkat' => $profil?->experience_level !== null ? $profil->experience_level->label().', diisi sendiri' : null,
                'bergabung' => 'Bergabung '.$user->created_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('M Y'),
                'foto' => FotoProfil::url($user),
            ],
            'pengalaman' => [
                'lengkap' => $lengkap,
                'ubah' => route('onboarding'),
                'rincian' => [
                    ['label' => 'Tingkat', 'nilai' => $profil?->experience_level?->label()],
                    ['label' => 'Pendakian selesai', 'nilai' => $pengalaman !== null ? number_format($pengalaman->completed_hikes_count, 0, ',', '.') : null],
                    ['label' => 'Tanjakan tertinggi', 'nilai' => $tertinggi !== null ? number_format($tertinggi, 0, ',', '.').' m' : null],
                    ['label' => 'Medan', 'nilai' => $medan !== '' ? $medan : null],
                    ['label' => 'Navigasi', 'nilai' => $pengalaman?->navigation_experience?->label()],
                ],
            ],
            'preferensi' => [
                'ubah' => route('onboarding', ['langkah' => 3]),
                'rincian' => [
                    ['label' => 'Jenis trip', 'nilai' => $preferensi?->preferred_trip_type?->labelPendek()],
                    ['label' => 'Lama pendakian', 'nilai' => $preferensi?->preferred_duration?->label()],
                    ['label' => 'Wilayah', 'nilai' => $wilayah !== '' ? $wilayah : null],
                ],
            ],
            'perlengkapan' => [
                'daftar' => $user->ownedItems()->orderBy('name')->orderBy('id')->get(['id', 'name'])
                    ->map(fn (UserOwnedItem $barang) => ['id' => $barang->id, 'nama' => $barang->name])
                    ->all(),
                'batas' => UserOwnedItem::BATAS,
            ],
            'kabar' => $kabar,
        ];
    }
}
