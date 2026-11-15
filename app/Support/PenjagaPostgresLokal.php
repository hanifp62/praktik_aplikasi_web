<?php

namespace App\Support;

use App\Exceptions\PostgresLokalDitolak;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Penjaga koneksi PostgreSQL lokal untuk `php artisan memount:lokal`.
 *
 * Memeriksa konfigurasi sebelum membuka soket jaringan atau menyentuh tabel, supaya
 * pengembang yang pernah menaruh kredensial luar di `.env` tidak pernah menjalankan
 * pengisian contoh lokal ke basis data di luar mesinnya sendiri.
 */
class PenjagaPostgresLokal
{
    public const KONEKSI = 'pgsql';

    /** @var array<int, string> */
    private const HOST_LOOPBACK = ['127.0.0.1', 'localhost', '::1'];

    /** @var array<int, string> */
    private const SKEMA_URL = ['pgsql', 'postgres', 'postgresql'];

    /**
     * @return array{host: string, port: int, database: string}
     *
     * @throws PostgresLokalDitolak
     */
    public function periksa(): array
    {
        if (! app()->environment('local')) {
            throw new PostgresLokalDitolak('Persiapan PostgreSQL lokal hanya boleh dijalankan di APP_ENV=local; lingkungan sekarang '.app()->environment().'.');
        }

        $url = trim((string) config('database.connections.'.self::KONEKSI.'.url'));

        if ($url !== '') {
            $bagian = parse_url($url);
            $skema = is_array($bagian) ? strtolower((string) ($bagian['scheme'] ?? '')) : '';
            $hostUrl = is_array($bagian) ? trim(strtolower((string) ($bagian['host'] ?? '')), '[]') : '';
            $jalurDb = is_array($bagian) ? ltrim((string) ($bagian['path'] ?? ''), '/') : '';

            if (! is_array($bagian) || ! in_array($skema, self::SKEMA_URL, true) || ! self::adalahHostLoopback($hostUrl) || $jalurDb === '') {
                $labelHost = $hostUrl !== '' ? $hostUrl : 'tidak terbaca';

                throw new PostgresLokalDitolak("DB_URL menunjuk host di luar mesin ini atau tidak valid ({$labelHost}); persiapan lokal dibatalkan sebelum membuka koneksi.");
            }
        }

        DB::purge(self::KONEKSI);

        $koneksi = DB::connection(self::KONEKSI);
        $pengemudi = $koneksi->getDriverName();

        if ($pengemudi !== 'pgsql') {
            throw new PostgresLokalDitolak("Koneksi pgsql bukan PostgreSQL ({$pengemudi}); persiapan lokal dibatalkan.");
        }

        $host = trim(strtolower((string) $koneksi->getConfig('host')), '[]');

        if (! self::adalahHostLoopback($host)) {
            throw new PostgresLokalDitolak("Host PostgreSQL bukan loopback lokal ({$host}); persiapan lokal menolak menyentuh server luar.");
        }

        $basisData = trim((string) $koneksi->getConfig('database'));

        if ($basisData === '') {
            throw new PostgresLokalDitolak('Nama basis data PostgreSQL lokal kosong; isi DB_DATABASE di .env lebih dulu.');
        }

        return [
            'host' => $host,
            'port' => (int) ($koneksi->getConfig('port') ?: 5432),
            'database' => $basisData,
        ];
    }

    /**
     * Memasang ekstensi PostGIS pada koneksi lokal yang sudah lolos pemeriksaan loopback.
     *
     * @throws PostgresLokalDitolak
     */
    public function siapkanEkstensiPostGis(): void
    {
        try {
            DB::connection(self::KONEKSI)->statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (Throwable $galat) {
            throw new PostgresLokalDitolak(
                'PostgreSQL lokal belum dapat dihubungi atau belum memuat ekstensi PostGIS: '.$galat->getMessage().'. Jalankan docker compose up -d postgres lebih dulu.',
                previous: $galat
            );
        }
    }

    public static function adalahHostLoopback(string $host): bool
    {
        return in_array(trim(strtolower($host), '[]'), self::HOST_LOOPBACK, true);
    }
}
