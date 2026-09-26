<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Logout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DestroyAccountRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateTrackPreferenceRequest;
use App\Support\PetunjukKataSandi;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman Profil: akun, profil pendakian, perekaman jejak, tampilan, dan penghapusan
 * akun. Setiap aksi bekerja pada pengguna yang sedang masuk; tidak ada id pengguna
 * yang diterima dari klien, jadi tidak ada akun orang lain yang dapat disentuh.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing(['profile', 'preference']);

        return Inertia::render('Profile/Edit', [
            'title' => 'Profil Akun',
            'status' => $request->session()->get('status'),
            'perluVerifikasi' => $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(),
            'rekamJejak' => (bool) $user->preference?->record_track,
            'pesanJejak' => $request->session()->get('status-jejak'),
            'petunjukSandi' => PetunjukKataSandi::teks(),

            // Ringkasan dari isian onboarding, dengan label yang sama. Yang belum diisi
            // dikirim null dan ditampilkan sebagai belum diisi, bukan ditebak (R-007).
            'profilPendakian' => [
                'lengkap' => $user->hasCompletedProfile(),
                'rincian' => [
                    ['label' => 'Tingkat pengalaman', 'nilai' => $user->profile?->experience_level?->label()],
                    ['label' => 'Preferensi wilayah', 'nilai' => $user->profile?->region_preference ?: null],
                    ['label' => 'Durasi yang Anda sukai', 'nilai' => $user->preference?->preferred_duration?->label()],
                    ['label' => 'Tipe perjalanan', 'nilai' => $user->preference?->preferred_trip_type?->label()],
                ],
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('profile');
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return to_route('profile');
    }

    /**
     * Perekaman default mati dan hanya menyala atas keputusan pendaki. Pendaki yang
     * belum pernah mengisi preferensi tetap dapat memutuskan; barisnya dibuat saat itu.
     */
    public function preference(UpdateTrackPreferenceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $rekam = $request->boolean('record_track');

        $user->preference()->updateOrCreate(
            ['user_id' => $user->id],
            ['record_track' => $rekam]
        );

        return to_route('profile')->with('status-jejak', $rekam
            ? 'Perekaman jejak dinyalakan untuk pendakian berikutnya.'
            : 'Perekaman jejak dimatikan. Jejak yang sudah tersimpan tidak ikut terhapus.');
    }

    /**
     * Sama persis dengan komponen Volt sebelumnya: keluar, lalu hapus baris pengguna.
     * Apa yang ikut terhapus dan apa yang dipertahankan tanpa nama diatur foreign key
     * dan docs/KEBIJAKAN-DATA-PRIBADI.md, bukan di sini.
     */
    public function destroy(DestroyAccountRequest $request, Logout $logout): RedirectResponse
    {
        tap($request->user(), $logout(...))->delete();

        return redirect('/');
    }
}
