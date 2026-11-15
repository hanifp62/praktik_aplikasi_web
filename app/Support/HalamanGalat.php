<?php

namespace App\Support;

use Illuminate\Http\Request;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Isi halaman galat, satu sumber untuk muat penuh (resources/views/errors) dan kunjungan
 * Inertia (halaman React Galat; ruling W4-6j, dititipkan ke W9).
 *
 * Halaman ini dirender justru ketika sesuatu rusak, jadi kelas ini tidak membaca basis
 * data, sesi, atau pengguna: alamat kembali datang dari permintaan itu sendiri, waktu
 * tunggu dari kepala Retry-After batas laju, dan tema dari cookie.
 */
final class HalamanGalat
{
    /** Kode yang punya halaman sendiri. Kode lain jatuh ke penanganan bawaan Laravel. */
    public const KODE = [403, 404, 419, 429, 500, 503];

    /**
     * @return array{kode: int, label: string, judul: string, penjelasan: list<string>, tindakan: list<array{label: string, href: string, utama: bool}>}
     */
    public static function isi(int $kode, ?Throwable $galat, Request $request): array
    {
        $beranda = ['label' => 'Kembali ke halaman utama', 'href' => url('/'), 'utama' => true];
        $ulang = ['label' => 'Muat ulang halaman', 'href' => self::alamatHalaman($request), 'utama' => true];

        [$judul, $penjelasan, $tindakan] = match ($kode) {
            403 => [
                'Anda tidak punya akses ke halaman ini',
                ['Halaman ini milik pengguna lain, atau memerlukan peran yang tidak Anda miliki. Kalau menurut Anda ini keliru, hubungi pengelola aplikasi.'],
                [$beranda],
            ],
            404 => [
                'Halaman ini tidak ditemukan',
                ['Alamatnya mungkin salah ketik, atau halamannya sudah dipindahkan. Jalur yang diarsipkan juga tidak lagi dapat dibuka.'],
                [$beranda, ['label' => 'Telusuri jalur', 'href' => url('/trails'), 'utama' => false]],
            ],
            419 => [
                'Sesi Anda sudah berakhir',
                [
                    'Ini biasa terjadi ketika halaman dibiarkan terbuka lama, misalnya dibuka di rumah lalu dilanjutkan di basecamp. Bukan kesalahan Anda, dan tidak ada data yang hilang selain isian yang belum sempat tersimpan.',
                    'Masuk kembali, lalu ulangi langkah terakhir Anda.',
                ],
                [['label' => 'Masuk kembali', 'href' => route('login'), 'utama' => true]],
            ],
            429 => [
                'Terlalu banyak permintaan dalam waktu singkat',
                [
                    'Aplikasi membatasi jumlah permintaan per menit supaya layanan tetap berjalan untuk semua pendaki. Permintaan terakhir Anda belum dijalankan, dan data yang sudah tersimpan tidak berubah.',
                    self::kalimatTunggu($galat),
                ],
                [$ulang],
            ],
            500 => [
                'Ada yang bermasalah di sisi kami',
                [
                    'Kesalahan ini bukan berasal dari yang Anda lakukan. Kejadiannya sudah tercatat untuk diperiksa.',
                    'Kalau Anda sedang bersiap berangkat, jangan menunggu aplikasi ini: pastikan status jalur langsung ke pengelola atau basecamp.',
                ],
                [$beranda],
            ],
            503 => [
                'Aplikasi sedang dalam perawatan',
                [
                    'Layanan dihentikan sementara dan akan kembali. Coba beberapa saat lagi.',
                    'Kalau Anda sedang bersiap berangkat, pastikan status jalur langsung ke pengelola atau basecamp.',
                ],
                [['label' => 'Coba lagi', 'href' => self::alamatHalaman($request), 'utama' => true]],
            ],
        };

        return ['kode' => $kode, 'label' => 'Galat '.$kode, 'judul' => $judul, 'penjelasan' => $penjelasan, 'tindakan' => $tindakan];
    }

    /**
     * Props halaman React Galat. Props bersama kerangka tidak ikut: hitungan Simpanan,
     * pendakian aktif, dan peran membaca basis data, padahal halaman ini dirender saat basis
     * data mungkin justru yang rusak. Merek, tema, dan tampilan dibaca dari config dan cookie,
     * sama dengan HandleInertiaRequests::share().
     *
     * @return array<string, mixed>
     */
    public static function props(int $kode, ?Throwable $galat, Request $request): array
    {
        $isi = self::isi($kode, $galat, $request);

        return [
            'title' => $isi['judul'],
            'galat' => $isi,
            'merek' => ['nama' => config('app.name'), 'tagline' => config('brand.tagline')],
            'demo' => PenjagaDataContoh::sedangDisajikan(),
            'tema' => ThemePreference::current($request),
            'tampilan' => ['efek' => DisplayPreference::efek($request), 'gerak' => DisplayPreference::gerak($request)],
        ];
    }

    /**
     * Penanganan galat untuk kunjungan Inertia (bootstrap/app.php). Kunjungan Inertia yang
     * berakhir dengan salah satu kode KODE mendapat halaman Galat, bukan dialog pengembang
     * Inertia yang memuat halaman HTML di dalam iframe. Muat penuh tetap dijawab halaman
     * Blade, dan galat server di mesin pengembang (APP_DEBUG) tetap memakai dialog itu supaya
     * rinciannya terbaca.
     */
    public static function tanggapiInertia(ExceptionResponse $tanggapan): Response
    {
        $kode = $tanggapan->statusCode();
        $request = $tanggapan->request;

        if (! $request->header(Header::INERTIA) || ! in_array($kode, self::KODE, true) || ($kode >= 500 && config('app.debug'))) {
            return $tanggapan->response;
        }

        Inertia::flushShared();

        // Alamat aksi (POST, PUT, DELETE) tidak dapat dibuka dengan GET; halaman galatnya
        // memakai alamat halaman asal supaya muat ulang dan tombol Kembali tetap berguna.
        if (! $request->isMethod('GET')) {
            $alamat = self::alamatHalaman($request);
            Inertia::resolveUrlUsing(fn () => $alamat);
        }

        return $tanggapan->render('Galat', self::props($kode, $tanggapan->exception, $request))->toResponse($request);
    }

    /**
     * Halaman yang dapat dibuka ulang dengan GET: alamat permintaan GET itu sendiri, atau
     * halaman asal (Referer) untuk aksi, hanya bila halaman asal ada di situs ini. Hasilnya
     * jalur relatif, sama dengan alamat halaman Inertia.
     */
    private static function alamatHalaman(Request $request): string
    {
        if ($request->isMethod('GET')) {
            return self::jalurRelatif($request->fullUrl(), $request) ?? '/';
        }

        return self::jalurRelatif((string) $request->headers->get('referer'), $request) ?? '/';
    }

    private static function jalurRelatif(string $alamat, Request $request): ?string
    {
        $asal = $request->getSchemeAndHttpHost();

        if ($alamat === '' || ($alamat !== $asal && ! str_starts_with($alamat, $asal.'/') && ! str_starts_with($alamat, $asal.'?'))) {
            return null;
        }

        $jalur = substr($alamat, strlen($asal));

        return $jalur === '' ? '/' : $jalur;
    }

    private static function kalimatTunggu(?Throwable $galat): string
    {
        $detik = $galat instanceof HttpExceptionInterface ? (int) ($galat->getHeaders()['Retry-After'] ?? 0) : 0;

        return $detik > 0
            ? 'Tunggu sekitar '.$detik.' detik, lalu muat ulang halaman.'
            : 'Tunggu sekitar satu menit, lalu muat ulang halaman.';
    }
}
