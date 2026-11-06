<?php

namespace App\Services\Admin;

use App\Models\Trail;
use App\Services\DataFreshnessService;

/**
 * Ringkasan kesehatan data jalur untuk konsol pengelola: apa yang perlu dibenahi lebih
 * dulu, dalam satu halaman.
 *
 * Tidak ada aturan baru di sini. Kekurangan tiap jalur dibaca dari
 * Trail::publishabilityReport(), gerbang yang sama dengan penerbitan dan rekomendasi
 * (R-008, R-022), dan status yang perlu ditinjau dari DataFreshnessService. Angkanya
 * dihitung setiap kali halaman dibuka, jadi ikut berubah begitu datanya diperbaiki.
 *
 * Jumlah query tetap: satu untuk jalur beserta hitungan relasinya, satu untuk
 * gunungnya, satu untuk status. Daftarnya dibatasi, hitungannya tidak.
 */
class DataHealthSummary
{
    public const BATAS_DAFTAR = 20;

    public function __construct(private DataFreshnessService $kesegaran) {}

    /**
     * @return array{
     *     jalurBelumLengkap: int,
     *     jalurTerbitBelumLengkap: int,
     *     tanpaGeometri: int|null,
     *     statusPerluDitinjau: int,
     *     daftar: array<int, array{id: int, nama: string, gunung: string|null, terbit: bool, kekurangan: array<int, string>, geometriUrl: string, posUrl: string}>,
     *     sisa: int
     * }
     */
    public function ringkasan(): array
    {
        $belumLengkap = Trail::query()
            ->active()
            ->withPublishabilityData()
            ->with('mountain:id,name')
            ->orderByDesc('is_published')
            ->orderBy('name')
            ->get()
            ->map(fn (Trail $trail) => ['jalur' => $trail, 'kekurangan' => $trail->publishabilityReport()])
            ->filter(fn (array $baris) => $baris['kekurangan'] !== [])
            ->values();

        return [
            'jalurBelumLengkap' => $belumLengkap->count(),
            'jalurTerbitBelumLengkap' => $belumLengkap->filter(fn (array $b) => $b['jalur']->is_published)->count(),

            // Tanpa PostGIS kolom geometri tidak ada, dan publishabilityReport() melewati
            // syarat itu. Hitungannya karena itu tidak diketahui, bukan nol (R-006).
            'tanpaGeometri' => Trail::spatialSupported()
                ? $belumLengkap->reject(fn (array $b) => $b['jalur']->hasGeometry())->count()
                : null,

            'statusPerluDitinjau' => $this->kesegaran->reviewCount(),

            'daftar' => $belumLengkap->take(self::BATAS_DAFTAR)->map(fn (array $b) => [
                'id' => $b['jalur']->id,
                'nama' => $b['jalur']->name,
                'gunung' => $b['jalur']->mountain?->name,
                'terbit' => (bool) $b['jalur']->is_published,
                'kekurangan' => $b['kekurangan'],
                'geometriUrl' => route('trails.geometry', $b['jalur']),
                'posUrl' => route('trails.checkpoints', $b['jalur']),
            ])->all(),

            'sisa' => max(0, $belumLengkap->count() - self::BATAS_DAFTAR),
        ];
    }
}
