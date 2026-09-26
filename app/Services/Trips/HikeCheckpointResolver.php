<?php

namespace App\Services\Trips;

use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;

/**
 * Pos berikutnya di mode pendakian (FR-15).
 *
 * Pos berikutnya adalah pos pertama menurut urutan yang belum tercapai. Aturan lama,
 * "satu setelah yang terdekat", melewati pos yang sedang dituju: pendaki 100 m sebelum
 * Pos 3 paling dekat ke Pos 3, lalu sistem menunjuk Pos 4.
 *
 * Sebuah pos dianggap tercapai setelah pendaki pernah berada dalam radius kedatangannya,
 * dan kemajuan itu diingat pada sesi sehingga tidak hilang ketika pendaki turun kembali
 * atau berputar.
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
     * Pos yang tercatat pada sesi sebagai tujuan sekarang, tanpa jarak: posisi pendaki
     * tidak disimpan di server, jadi jaraknya hanya ada sesaat setelah posisi dikirim.
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
     * Menentukan pos berikutnya dari posisi terkini, dan mencatat kemajuannya pada sesi.
     *
     * @param  array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>  $checkpoints
     * @return array{checkpoint: array{id: int, name: string, sequence: int}, distanceMeters: float|null}|null
     */
    public function resolve(array $checkpoints, ?HikingSession $session, float $latitude, float $longitude): ?array
    {
        if ($checkpoints === []) {
            return null;
        }

        $radius = (int) config('hiking.hike_mode.checkpoint_arrival_radius_m');
        $reached = (int) ($session?->reached_checkpoint_sequence ?? 0);

        // Pendaki bisa membuka Hike Mode di tengah jalur, jadi pos sebelum yang terdekat
        // dianggap terlewati. Yang terdekat sendiri hanya tercapai bila masuk radius.
        $nearest = $this->nearest($checkpoints, $latitude, $longitude);

        if ($nearest !== null) {
            $reached = max($reached, $nearest['sequence'] - 1);
        }

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['lat'] === null || $checkpoint['lng'] === null || $checkpoint['sequence'] <= $reached) {
                continue;
            }

            if ($this->haversineMeters($latitude, $longitude, $checkpoint['lat'], $checkpoint['lng']) <= $radius) {
                // Kemajuan tidak pernah mundur, sehingga pendaki yang turun kembali
                // tidak dikembalikan ke pos yang sudah ia lewati.
                $reached = max($reached, $checkpoint['sequence']);
            }
        }

        $next = null;

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['sequence'] > $reached) {
                $next = $checkpoint;
                break;
            }
        }

        // Seluruh pos sudah dilewati: tetap tunjukkan yang terakhir sebagai rujukan.
        $next ??= end($checkpoints) ?: null;

        if ($next === null) {
            return null;
        }

        $session?->update([
            'reached_checkpoint_sequence' => $reached,
            'current_checkpoint_id' => $next['id'],
        ]);

        return [
            'checkpoint' => ['id' => $next['id'], 'name' => $next['name'], 'sequence' => $next['sequence']],
            'distanceMeters' => ($next['lat'] === null || $next['lng'] === null)
                ? null
                : round($this->haversineMeters($latitude, $longitude, $next['lat'], $next['lng'])),
        ];
    }

    /**
     * @param  array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>  $checkpoints
     * @return array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}|null
     */
    private function nearest(array $checkpoints, float $latitude, float $longitude): ?array
    {
        $nearest = null;
        $shortest = null;

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['lat'] === null || $checkpoint['lng'] === null) {
                continue;
            }

            $distance = $this->haversineMeters($latitude, $longitude, $checkpoint['lat'], $checkpoint['lng']);

            if ($shortest === null || $distance < $shortest) {
                $shortest = $distance;
                $nearest = $checkpoint;
            }
        }

        return $nearest;
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = (int) config('hiking.hike_mode.earth_radius_m');
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
