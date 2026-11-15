<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfilePhotoRequest;
use App\Services\Profil\FotoProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto profil milik pengguna yang masuk (W7). Tidak ada id pengguna di rute: setiap aksi,
 * termasuk menyajikan berkasnya, bekerja pada pemilik sesi, jadi foto orang lain tidak dapat
 * dibuka dengan menebak alamat.
 */
class ProfilePhotoController extends Controller
{
    public function show(Request $request): StreamedResponse
    {
        $path = $request->user()->profile?->avatar_path;
        $disk = Storage::disk(FotoProfil::disk());

        abort_if(! FotoProfil::sah($path) || ! $disk->exists($path), 404);

        // Foto pribadi tidak boleh disimpan proxy bersama.
        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function store(UpdateProfilePhotoRequest $request, FotoProfil $foto): RedirectResponse
    {
        $foto->simpan($request->user(), $request->file('foto'));

        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil diganti.']);
    }

    public function destroy(Request $request, FotoProfil $foto): RedirectResponse
    {
        $foto->hapus($request->user());

        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil dihapus. Inisial nama Anda dipakai lagi.']);
    }
}
