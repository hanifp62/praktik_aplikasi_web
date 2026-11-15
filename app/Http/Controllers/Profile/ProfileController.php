<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Logout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DestroyAccountRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateTrackPreferenceRequest;
use App\Services\AccountDeletionService;
use App\Services\Profil\HalamanProfil;
use App\Support\PetunjukKataSandi;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman Profil (spec v3 bagian 6.15, prototype 14): identitas, pengalaman, preferensi,
 * perlengkapan pribadi, tampilan, privasi, akun, pintu konsol, dan penghapusan akun. Isi
 * pendakiannya disusun HalamanProfil. Setiap aksi bekerja pada pengguna yang sedang masuk;
 * tidak ada id pengguna yang diterima dari klien, jadi tidak ada akun orang lain yang dapat
 * disentuh.
 */
class ProfileController extends Controller
{
    public function edit(Request $request, HalamanProfil $halaman): Response
    {
        $user = $request->user()->loadMissing(['profile', 'experience', 'preference']);

        return Inertia::render('Profile/Edit', [
            'title' => 'Profil Akun',
            'status' => $request->session()->get('status'),
            'perluVerifikasi' => $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(),
            'rekamJejak' => (bool) $user->preference?->record_track,
            'pesanJejak' => $request->session()->get('status-jejak'),
            'petunjukSandi' => PetunjukKataSandi::teks(),
            ...$halaman->untuk($user, $request->session()->get('kabar-profil')),
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

        // Kalimat ini tidak mengklaim perekaman berhenti di tab atau perangkat lain yang mode
        // lapangannya masih terbuka; "tidak diterima" ditegakkan server (PenerimaanJejak, ruling W6-P2).
        return to_route('profile')->with('status-jejak', $rekam
            ? 'Perekaman jejak dinyalakan untuk mode lapangan yang dibuka dengan sinyal sesudah ini.'
            : 'Perekaman jejak dimatikan untuk mode lapangan yang dibuka dengan sinyal sesudah ini. Titik yang belum terkirim tetap di perangkat dan tidak diterima Memount selama perekaman mati; jejak yang sudah tersimpan di Memount tidak ikut terhapus.');
    }

    /**
     * Keluar dari sesi aktif, lalu menjalankan penghapusan akun dan pembersihan residu sesuai
     * docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2 sampai 6 lewat AccountDeletionService.
     *
     * Data Memount di perangkat ini ikut dihapus, karena KEBIJAKAN bagian 4 menghapus GPS presisi
     * pada penghapusan akun, termasuk titik jejak yang belum terkirim (ruling W6-11b). Halaman
     * menghapusnya sesudah jawaban ini berhasil, dan Clear-Site-Data menjadi lapis kedua di
     * peramban yang mendukungnya. Kata sandi yang salah ditolak DestroyAccountRequest sebelum
     * sampai ke sini, jadi tidak ada yang dihapus.
     */
    public function destroy(DestroyAccountRequest $request, Logout $logout, AccountDeletionService $penghapusAkun): RedirectResponse
    {
        $user = $request->user();
        $logout();
        $penghapusAkun->hapus($user);

        return redirect('/')->header('Clear-Site-Data', '"storage"');
    }
}
