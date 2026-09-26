<?php

namespace App\Services\Admin;

use Illuminate\Database\Migrations\Migrator;

/**
 * Migrasi yang belum dijalankan di basis data yang sedang dipakai aplikasi (R-024).
 *
 * Suite berjalan di SQLite yang migrasinya selalu lengkap, sehingga ia tidak pernah
 * melihat basis data produksi yang tertinggal. Kelas kegagalan itu pernah membuat halaman
 * Jelajahi galat di tangan pemilik sementara seluruh test hijau (HANDOVER §6). Laporan ini
 * dibaca langsung dari basis data yang dipakai, baca-saja.
 */
class MigrationStatus
{
    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return array{tersedia: bool, tertunda: list<string>}
     */
    public function laporan(): array
    {
        if (! $this->migrator->repositoryExists()) {
            return ['tersedia' => false, 'tertunda' => []];
        }

        $berkas = array_keys($this->migrator->getMigrationFiles([database_path('migrations'), ...$this->migrator->paths()]));

        return [
            'tersedia' => true,
            'tertunda' => array_values(array_diff($berkas, $this->migrator->getRepository()->getRan())),
        ];
    }
}
