<?php

namespace App\Services\DataResmi;

use App\Models\OfficialStatus;
use App\Support\ImageSanitizer;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Arsip bukti status resmi (spec v3 bagian 10.7; rencana induk W8 butir 6; ruling W8-R9).
 *
 * Kurator mengunggah salinan surat, laman, atau unggahan akun resmi pengelola. Tipe berkas
 * diperiksa dari isinya (tanda awal PDF, atau gambar yang terbaca GD), bukan dari nama atau
 * tebakan tipe. Gambar ditulis ulang lebih dulu tanpa EXIF dan dikecilkan (ImageSanitizer, PRD
 * §81 dan §82); PDF disimpan apa adanya. SHA-256 dihitung dari bita yang tersimpan, nama berkas
 * acak di disk privat yang dapat diganti (filesystems.evidence_disk), dan berkas hanya disajikan
 * lewat rute curator.evidence.show sesudah sidiknya cocok.
 *
 * Arsip otomatis dari URL yang ditempel tidak dibangun: mengambil alamat sembarang dari server
 * membuka SSRF, jadi URL disimpan sebagai rujukan dan salinannya diunggah kurator (Konflik
 * koordinator, ruling W8-R9).
 */
final class ArsipBukti
{
    public const MAP = 'bukti-status';

    private const GAMBAR = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Aturan validasi berkas bukti, dipakai Form Request meja kurator.
     *
     * @return array<int, mixed>
     */
    public static function aturan(): array
    {
        return [
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:'.(int) config('hiking.uploads.evidence_max_kb'),
            function (string $atribut, mixed $berkas, Closure $gagal): void {
                if ($berkas instanceof UploadedFile && self::jenisDariIsi($berkas) === null) {
                    $gagal('Bukti harus berupa PDF atau foto JPG, PNG, atau WebP.');
                }
            },
        ];
    }

    /**
     * 'pdf' atau 'gambar' menurut isi berkas, atau null bila bukan keduanya.
     */
    public static function jenisDariIsi(UploadedFile $berkas): ?string
    {
        $path = (string) $berkas->getRealPath();

        if ($path === '' || ! is_readable($path)) {
            return null;
        }

        $pegangan = @fopen($path, 'rb');
        $awal = $pegangan === false ? '' : (string) fread($pegangan, 5);

        if ($pegangan !== false) {
            fclose($pegangan);
        }

        $ekstensi = strtolower($berkas->getClientOriginalExtension());

        if ($awal === '%PDF-' && $ekstensi === 'pdf') {
            return 'pdf';
        }

        if (in_array($ekstensi, self::GAMBAR, true) && @getimagesize($path) !== false) {
            return 'gambar';
        }

        return null;
    }

    /**
     * @return array{path: string, sha256: string}
     */
    public function simpan(UploadedFile $berkas): array
    {
        $jenis = self::jenisDariIsi($berkas);

        if ($jenis === null) {
            throw ValidationException::withMessages(['bukti' => 'Bukti harus berupa PDF atau foto JPG, PNG, atau WebP.']);
        }

        if ($jenis === 'gambar') {
            try {
                ImageSanitizer::sanitize((string) $berkas->getRealPath(), (int) config('hiking.uploads.evidence_dimension'));
            } catch (RuntimeException) {
                throw ValidationException::withMessages(['bukti' => 'Foto bukti tidak dapat diproses. Unggah PDF atau foto lain.']);
            }
        }

        $sha256 = (string) hash_file('sha256', (string) $berkas->getRealPath());
        $path = $berkas->store(self::MAP, self::disk());

        if ($path === false) {
            throw ValidationException::withMessages(['bukti' => 'Bukti gagal disimpan. Coba lagi.']);
        }

        return ['path' => $path, 'sha256' => $sha256];
    }

    /**
     * Arsip catatan ini masih ada dan bita-bitanya masih cocok dengan sidik yang dicatat.
     */
    public function utuh(OfficialStatus $status): bool
    {
        $path = $status->evidence_archive_path;
        $disk = Storage::disk(self::disk());

        return self::sah($path)
            && $status->evidence_sha256 !== null
            && $disk->exists($path)
            && hash_equals($status->evidence_sha256, hash('sha256', (string) $disk->get($path)));
    }

    /**
     * Hanya berkas yang ditulis layanan ini: satu nama acak langsung di dalam map arsip.
     */
    public static function sah(?string $path): bool
    {
        return $path !== null && preg_match('#^'.self::MAP.'/[A-Za-z0-9]+\.(pdf|jpe?g|png|webp)$#', $path) === 1;
    }

    public static function disk(): string
    {
        return (string) config('filesystems.evidence_disk');
    }
}
