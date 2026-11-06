<?php

namespace App\Services\Trips;

use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;

/**
 * Kemajuan antarpos di mode lapangan (FR-15).
 *
 * Pos berikutnya dihitung di perangkat dari data yang sudah dimuat (spec v3 bagian 6.12,
 * resources/js/components/mendaki/hitung.ts): posisi pendaki tidak dikirim ke server (PRD
 * §82, §145.1). Yang diterima server hanya urutan pos yang sudah dicapai, lalu dicatat pada
 * sesi supaya strip pendakian, Beranda, dan ringkasan tahu sampai mana pendakian ini.
 *
 * Kemajuan tidak pernah mundur: pendaki yang turun kembali atau mengirim ulang tiba di pos
 * yang lebih awal (antrean luring yang diputar ulang) tidak menghapus pos yang sudah dicapai.
 */
class HikeCheckpointResolver
{
    /**
     * Koordinat dibaca dari kolom biasa, bukan diekstrak dari kolom geografi. Sumber
     * yang sama bekerja di mana pun, termasuk koneksi tanpa PostGIS.
     *
     * @return array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>
     */
    public function checkpoints(Trail $trail): array
    {
        return $trail->checkpoints
            ->sortBy('sequence')
            ->map(fn (Checkpoint $checkpoint) => [
                'id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'sequence' => (int) $checkpoint->sequence,
                'lat' => $checkpoint->latitude === null ? null : (float) $checkpoint->latitude,
                'lng' => $checkpoint->longitude === null ? null : (float) $checkpoint->longitude,
            ])
            ->values()
            ->all();
    }

    /**
     * Pos yang tercatat pada sesi sebagai tujuan sekarang.
     *
     * @param  array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>  $checkpoints
     * @return array{id: int, name: string, sequence: int}|null
     */
    public function current(?HikingSession $session, array $checkpoints): ?array
    {
        $id = $session?->current_checkpoint_id;

        if ($id === null) {
            return null;
        }

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['id'] === (int) $id) {
                return ['id' => $checkpoint['id'], 'name' => $checkpoint['name'], 'sequence' => $checkpoint['sequence']];
            }
        }

        return null;
    }

    /**
     * Mencatat pendaki tiba di pos berurutan $urutan. Satu UPDATE bersyarat: baris hanya berubah
     * bila urutan yang tercatat lebih kecil, jadi dua kiriman yang tiba bersamaan tidak saling
     * menurunkan kemajuan. Pos yang dituju menjadi pos pertama sesudahnya, atau pos terakhir
     * bila semua sudah dicapai.
     *
     * @param  array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>  $checkpoints
     */
    public function tiba(HikingSession $session, array $checkpoints, int $urutan): void
    {
        $tujuan = null;

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['sequence'] > $urutan) {
                $tujuan = $checkpoint;
                break;
            }
        }

        $tujuan ??= end($checkpoints) ?: null;

        HikingSession::query()
            ->whereKey($session->id)
            ->where(fn ($kueri) => $kueri->whereNull('reached_checkpoint_sequence')->orWhere('reached_checkpoint_sequence', '<', $urutan))
            ->update([
                'reached_checkpoint_sequence' => $urutan,
                'current_checkpoint_id' => $tujuan['id'] ?? null,
                'updated_at' => now(),
            ]);
    }
}
