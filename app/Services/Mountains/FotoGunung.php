<?php

namespace App\Services\Mountains;

use App\Models\Mountain;
use App\Models\MountainPhoto;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 8.7; rencana induk W8 butir 8; ruling W8-R19).
 * Berkas unggahan admin ditulis ulang lebih dulu (EXIF hilang, sisi terpanjang dibatasi,
 * ImageSanitizer), lalu disimpan dengan nama acak di disk yang dapat diganti
 * (filesystems.mountain_photos_disk) beserta ukuran dan sidik SHA-256-nya. Satu foto per gunung:
 * mengganti foto menghapus berkas lamanya. Setiap simpan dan hapus diaudit.
 */
final class FotoGunung
{
    public const MAP = 'mountain-photos';

    private const DIAUDIT = ['mountain_id', 'path', 'width', 'height', 'sha256', 'creator', 'license', 'source_url', 'attribution', 'alt_text', 'uploaded_by'];

    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array{creator: string, license: string, source_url?: string|null, attribution: string, alt_text: string}  $isian
     */
    public function simpan(Mountain $gunung, UploadedFile $berkas, array $isian, User $admin): MountainPhoto
    {
        $sumber = (string) $berkas->getRealPath();

        try {
            ImageSanitizer::sanitize($sumber, (int) config('hiking.uploads.mountain_photo_dimension'));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['foto' => 'Foto tidak dapat diproses. Pilih foto JPG, PNG, atau WebP yang lain.']);
        }

        clearstatcache(true, $sumber);
        [$lebar, $tinggi] = getimagesize($sumber) ?: [0, 0];
        $sidik = (string) hash_file('sha256', $sumber);
        $path = $berkas->store(self::MAP, self::disk());

        if ($path === false) {
            throw ValidationException::withMessages(['foto' => 'Foto gagal disimpan. Coba lagi.']);
        }

        $foto = MountainPhoto::query()->firstOrNew(['mountain_id' => $gunung->id]);
        $sebelum = $foto->exists ? $foto->only(self::DIAUDIT) : null;
        $lama = $foto->exists ? $foto->path : null;

        $foto->fill([
            'creator' => $isian['creator'],
            'license' => $isian['license'],
            'source_url' => $isian['source_url'] ?? null,
            'attribution' => $isian['attribution'],
            'alt_text' => $isian['alt_text'],
        ]);
        $foto->forceFill(['mountain_id' => $gunung->id, 'path' => $path, 'width' => $lebar, 'height' => $tinggi, 'sha256' => $sidik, 'uploaded_by' => $admin->id]);

        try {
            DB::transaction(function () use ($foto, $admin, $sebelum): void {
                $foto->save();
                $this->audit->record($admin, 'mountain_photo.saved', $foto, $sebelum, $foto->only(self::DIAUDIT));
            });
        } catch (Throwable $galat) {
            // Berkas baru tidak boleh tertinggal tanpa baris yang menunjuknya.
            $this->hapusBerkas($path);

            throw $galat;
        }

        $this->hapusBerkas($lama);

        return $foto;
    }

    public function hapus(Mountain $gunung, User $admin): void
    {
        $foto = MountainPhoto::query()->where('mountain_id', $gunung->id)->first();

        if ($foto === null) {
            return;
        }

        DB::transaction(function () use ($foto, $admin): void {
            $this->audit->record($admin, 'mountain_photo.deleted', $foto, $foto->only(self::DIAUDIT), null);
            $foto->delete();
        });

        $this->hapusBerkas($foto->path);
    }

    /**
     * Hanya berkas yang ditulis layanan ini: satu nama acak langsung di dalam map foto gunung.
     */
    public static function sah(?string $path): bool
    {
        return $path !== null && preg_match('#^'.self::MAP.'/[A-Za-z0-9]+(\.[A-Za-z0-9]+)?$#', $path) === 1;
    }

    public static function disk(): string
    {
        return (string) config('filesystems.mountain_photos_disk');
    }

    /**
     * Bentuk untuk halaman gunung, dengan penanda versi dari sidik supaya foto baru tidak tertahan
     * cache peramban; null bila gunung belum punya foto (halaman memakai visual data).
     *
     * @return array{url: string, alt: string, atribusi: string, lisensi: string, pembuat: string, sumber: string|null, lebar: int, tinggi: int}|null
     */
    public static function bentuk(?MountainPhoto $foto, Mountain $gunung): ?array
    {
        if ($foto === null || ! self::sah($foto->path)) {
            return null;
        }

        return [
            'url' => route('mountains.photo', ['mountain' => $gunung, 'v' => substr($foto->sha256, 0, 12)]),
            'alt' => $foto->alt_text,
            'atribusi' => $foto->attribution,
            'lisensi' => $foto->license->label(),
            'pembuat' => $foto->creator,
            'sumber' => $foto->source_url,
            'lebar' => $foto->width,
            'tinggi' => $foto->height,
        ];
    }

    private function hapusBerkas(?string $path): void
    {
        if (self::sah($path)) {
            Storage::disk(self::disk())->delete($path);
        }
    }
}
