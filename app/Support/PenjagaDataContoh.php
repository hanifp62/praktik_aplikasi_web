<?php

namespace App\Support;

use App\Exceptions\DataContohDitolak;
use Illuminate\Support\Facades\DB;

/**
 * Penjaga data contoh (DK-35, ruling W1-R2).
 *
 * `.env` repo ini menunjuk Supabase produksi, jadi perintah yang mengosongkan lalu mengisi
 * basis data wajib membuktikan dulu bahwa sasarannya berkas SQLite lokal. Tiga syarat
 * diperiksa berurutan, sebelum apa pun ditulis:
 *
 * 1. APP_ENV=local, bukan testing, staging, atau produksi;
 * 2. koneksi demo benar-benar SQLite, tidak diubah URL atau pengemudi lain;
 * 3. berkasnya di dalam folder database/ dan berakhiran .sqlite, bukan :memory:, bukan
 *    symlink, dan bukan jalur yang memanjat keluar lewat "..".
 *
 * Penjaga hanya membaca konfigurasi; ia tidak membuat berkas dan tidak membuka koneksi.
 *
 * Tinjauan W1-9a #3: syarat 1 (APP_ENV=local) adalah penegas niat, bukan pagar mutlak.
 * Opsi global `--env=local` di argv mengalahkan APP_ENV (Illuminate\Foundation\Console\
 * Kernel membaca opsi itu lebih dulu lewat EnvironmentDetector), sehingga siapa pun dapat
 * melangkahi syarat 1 dari baris perintah. Pagar yang benar-benar menahan adalah syarat 2
 * dan 3: koneksi demo ditulis tetap di config/database.php (bukan dibaca dari .env) dan
 * berkasnya wajib berada di dalam folder database/, ditambah putuskanSaluranLuar() yang
 * memutuskan cache, antrean, surel, disk, dan broadcasting dari nilai .env sebelum
 * migrate/seed berjalan.
 */
final class PenjagaDataContoh
{
    public const KONEKSI = 'demo';

    /**
     * @return string Jalur berkas SQLite data contoh.
     *
     * @throws DataContohDitolak
     */
    public function periksa(): string
    {
        if (! app()->environment('local')) {
            throw new DataContohDitolak('Data contoh hanya boleh diisi di APP_ENV=local; lingkungan sekarang '.app()->environment().'.');
        }

        DB::purge(self::KONEKSI);

        $koneksi = DB::connection(self::KONEKSI);
        $pengemudi = $koneksi->getDriverName();

        if ($pengemudi !== 'sqlite') {
            throw new DataContohDitolak("Koneksi demo bukan SQLite ({$pengemudi}); data contoh tidak ditulis ke basis data lain.");
        }

        $berkas = (string) $koneksi->getConfig('database');
        $folder = realpath(database_path());
        $induk = $berkas === '' || $berkas === ':memory:' ? false : realpath(dirname($berkas));

        if ($folder === false || $induk !== $folder || ! str_ends_with($berkas, '.sqlite')) {
            throw new DataContohDitolak("Berkas demo di luar folder database/ ditolak: {$berkas}.");
        }

        // Tinjauan W1-9a #2: realpath() pada induk berkas mengikuti symlink sampai ke
        // sasarannya, sehingga pemeriksaan folder di atas tidak menangkap berkas yang
        // sendiri adalah symlink (mis. database/demo.sqlite -> /tempat/lain/luar). is_link()
        // memeriksa jalur itu sendiri, bukan sasarannya.
        if (is_link($berkas)) {
            throw new DataContohDitolak("Berkas demo adalah symlink, ditolak: {$berkas}.");
        }

        return $berkas;
    }

    /**
     * Memutuskan saluran keluar yang diatur `.env` dari koneksi nyata, sebelum migrate/seed
     * data contoh berjalan (tinjauan W1-9a #1).
     *
     * `periksa()` hanya membuktikan koneksi BASIS DATA tidak menyentuh produksi. Cache,
     * antrean, surel, dan disk masing-masing punya kunci `.env` sendiri
     * (CACHE_STORE/DB_CACHE_CONNECTION, QUEUE_CONNECTION, MAIL_MAILER, FILESYSTEM_DISK,
     * REPORT_PHOTOS_DISK) yang tidak disentuh syarat di periksa(). Bahayanya nyata: store
     * cache "database" yang sudah pernah ter-resolve (mis. lewat kode lain yang berjalan
     * sebelum command ini) tetap memegang koneksi basis data DEFAULT saat itu, karena
     * instance store disimpan sekali, bukan dicari ulang setiap panggilan.
     * `OfficialStatus::saved()` memanggil `Cache::forget()` tanpa nama store selama
     * seeding; tanpa metode ini, panggilan itu bisa menimpa store nyata yang menunjuk basis
     * data produksi walau koneksi basis data sendiri sudah benar.
     *
     * Dipanggil sesudah periksa() berhasil dan sebelum satu tabel pun disentuh (sebelum
     * touch/migrate:fresh/db:seed), dan oleh isolasiBilaMenyajikanDemo() saat aplikasi
     * menyajikan data contoh.
     */
    public function putuskanSaluranLuar(): void
    {
        $cacheLama = config('cache.default');
        config(['cache.default' => 'array']);
        app('cache')->forgetDriver($cacheLama);

        config(['queue.default' => 'sync']);

        if (! is_array(config('mail.mailers.array'))) {
            config(['mail.mailers.array' => ['transport' => 'array']]);
        }
        config(['mail.default' => 'array']);
        app('mail.manager')->forgetMailers();

        config(['filesystems.default' => 'local']);

        // Tinjauan akhir W1, I-3: foto laporan kondisi disimpan ke disk sendiri
        // (ConditionReportController), bukan ke disk bawaan, dan REPORT_PHOTOS_DISK di .env
        // dapat menunjuk bucket Supabase produksi.
        config(['filesystems.report_photos_disk' => 'local']);

        // Tinjauan W1-9b #2: config/broadcasting.php tidak ada di config/ aplikasi, tapi
        // itu tidak berarti config('broadcasting.*') kosong. LoadConfiguration memuat
        // konfigurasi dasar framework untuk setiap berkas yang tidak ditimpa aplikasi
        // (Illuminate\Foundation\Bootstrap\LoadConfiguration::getBaseConfiguration()),
        // dan vendor/laravel/framework/config/broadcasting.php mendefinisikan
        // 'default' => env('BROADCAST_CONNECTION', 'null'). Jadi config('broadcasting.default')
        // benar-benar hidup dari BROADCAST_CONNECTION di .env. Risikonya kecil karena
        // tidak ada kode aplikasi yang memanggil fasad Broadcast, tapi saluran ini tetap
        // diputus demi konsistensi dengan cache/antrean/surel/disk di atas.
        config(['broadcasting.default' => 'null']);
    }

    /**
     * Memutus saluran keluar saat aplikasi menyajikan data contoh (tinjauan akhir W1, I-3).
     *
     * Dipanggil di awal AppServiceProvider::boot(). Bila koneksi basis data bawaan adalah
     * koneksi demo, putuskanSaluranLuar() memutus cache, antrean, surel, kedua disk, dan
     * broadcasting dari nilai .env, dan sesi memakai berkas. Dengan begitu isolasinya tidak
     * bergantung pada operator menyalin baris penyaji yang panjang. Hanya membaca dan
     * menulis config: tidak membuka koneksi dan tidak menjalankan query.
     */
    public function isolasiBilaMenyajikanDemo(): void
    {
        if (config('database.default') !== self::KONEKSI) {
            return;
        }

        $this->putuskanSaluranLuar();
        config(['session.driver' => 'file']);
    }

    /**
     * Apakah aplikasi SEDANG menyajikan data contoh, dari sumber mana pun (tinjauan
     * W1-9a #4).
     *
     * Dua sumber independen: `config('app.demo')` (operator menaikkan APP_DEMO secara
     * eksplisit, jalur yang dicetak MemountDemo sesudah seeding) dan koneksi basis data
     * default yang sedang benar-benar `demo` (operator sudah memakai DB_CONNECTION=demo
     * tetapi lupa menaikkan APP_DEMO). Keduanya dibaca dari config saja, tanpa membuka
     * koneksi atau menjalankan query, supaya metode ini boleh dipanggil di setiap render
     * halaman tanpa menambah anggaran query (§96).
     */
    public static function sedangDisajikan(): bool
    {
        return (bool) config('app.demo') || config('database.default') === self::KONEKSI;
    }
}
