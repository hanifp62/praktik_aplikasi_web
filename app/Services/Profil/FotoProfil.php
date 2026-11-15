<?php

namespace App\Services\Profil;

use App\Models\User;
use App\Support\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Foto profil (spec v3 bagian 6.15; rencana induk W7 butir 5). Berkas unggahan ditulis ulang
 * lebih dulu, sebelum disimpan: EXIF (termasuk koordinat GPS) hilang dan sisi terpanjangnya
 * dibatasi (ImageSanitizer, PRD §81 dan §82). Nama berkas acak di disk privat yang dapat diganti
 * (filesystems.avatars_disk), dan berkasnya hanya disajikan kepada pemiliknya lewat rute
 * profile.photo.show. Mengganti atau menghapus foto menghapus berkas lamanya.
 */
final class FotoProfil
{
    public const MAP = 'avatars';

    public function simpan(User $user, UploadedFile $berkas): void
    {
        try {
            ImageSanitizer::sanitize((string) $berkas->getRealPath(), (int) config('hiking.uploads.avatar_dimension'));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['foto' => 'Foto tidak dapat diproses. Pilih foto JPG, PNG, atau WebP yang lain.']);
        }

        $lama = $user->profile?->avatar_path;
        $baru = $berkas->store(self::MAP, self::disk());

        if ($baru === false) {
            throw ValidationException::withMessages(['foto' => 'Foto gagal disimpan. Coba lagi.']);
        }

        $user->profile()->updateOrCreate([], ['avatar_path' => $baru]);
        $this->hapusBerkas($lama);
    }

    public function hapus(User $user): void
    {
        $lama = $user->profile?->avatar_path;

        if ($lama === null) {
            return;
        }

        $user->profile()->update(['avatar_path' => null]);
        $this->hapusBerkas($lama);
    }

    public function hapusBerkas(?string $path): void
    {
        if (self::sah($path)) {
            Storage::disk(self::disk())->delete($path);
        }
    }

    /**
     * Hanya berkas yang ditulis layanan ini: satu nama acak langsung di dalam map avatars. Kolom
     * avatar_path sudah ada sebelum W7, jadi nilai lain (map lain, bertingkat, atau berisi "..")
     * tidak pernah disajikan atau dihapus, dan berkas lain di disk yang sama tidak tersentuh.
     */
    public static function sah(?string $path): bool
    {
        return $path !== null && preg_match('#^'.self::MAP.'/[A-Za-z0-9]+(\.[A-Za-z0-9]+)?$#', $path) === 1;
    }

    /**
     * Alamat foto pemiliknya, dengan penanda versi dari nama berkas supaya foto baru tidak
     * tertahan cache peramban; null bila belum ada foto (avatar memakai inisial).
     */
    public static function url(User $user): ?string
    {
        $path = $user->profile?->avatar_path;

        return self::sah($path) ? route('profile.photo.show', ['v' => substr(sha1($path), 0, 12)]) : null;
    }

    public static function disk(): string
    {
        return (string) config('filesystems.avatars_disk');
    }
}
