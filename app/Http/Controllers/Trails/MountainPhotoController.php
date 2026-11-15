<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Models\Mountain;
use App\Services\Mountains\FotoGunung;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas foto gunung berlisensi untuk halaman gunung (DK-37; ruling W8-R19). Hanya berkas yang
 * ditulis FotoGunung yang disajikan; gunung terarsip dan gunung tanpa foto menjawab 404.
 */
class MountainPhotoController extends Controller
{
    public function __invoke(Mountain $mountain): StreamedResponse
    {
        abort_if($mountain->isArchived(), 404);
        $mountain->loadMissing('photo');

        $path = $mountain->photo?->path;
        $disk = Storage::disk(FotoGunung::disk());

        abort_if(! FotoGunung::sah($path) || ! $disk->exists($path), 404);

        // Halaman gunung hanya untuk pengguna masuk, jadi tidak disimpan proxy bersama.
        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
