<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PhotoLicense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SimpanFotoGunungRequest;
use App\Models\Mountain;
use App\Services\Mountains\FotoGunung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Foto gunung berlisensi di konsol admin (DK-37; spec v3 bagian 8.7 dan 9.3; ruling W8-R19):
 * pratinjau dengan atribusinya, unggah atau ganti, dan hapus. Hanya admin.
 */
class MountainPhotoManagerController extends Controller
{
    public function edit(Mountain $mountain): Response
    {
        $this->authorize('update', $mountain);
        $mountain->loadMissing('photo');

        return Inertia::render('Admin/MountainPhoto', [
            'title' => 'Foto '.$mountain->name,
            'gunung' => ['id' => $mountain->id, 'nama' => $mountain->name],
            'foto' => FotoGunung::bentuk($mountain->photo, $mountain),
            'lisensi' => PhotoLicense::pilihan(),
            'batasKb' => (int) config('hiking.uploads.mountain_photo_max_kb'),
        ]);
    }

    public function store(SimpanFotoGunungRequest $request, Mountain $mountain, FotoGunung $foto): RedirectResponse
    {
        $foto->simpan($mountain, $request->file('foto'), $request->safe()->except('foto'), $request->user());

        return to_route('admin.mountains.photo', $mountain)->with('status', 'Foto '.$mountain->name.' tersimpan beserta atribusinya.');
    }

    public function destroy(Request $request, Mountain $mountain, FotoGunung $foto): RedirectResponse
    {
        $this->authorize('update', $mountain);

        $foto->hapus($mountain, $request->user());

        return to_route('admin.mountains.photo', $mountain)->with('status', 'Foto '.$mountain->name.' dihapus. Halaman gunung kembali memakai visual data.');
    }
}
