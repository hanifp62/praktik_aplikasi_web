<?php

namespace App\Livewire\Trips;

use App\Models\Checkpoint;
use App\Models\TripPlan;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-15 hike mode: deliberately minimal (PRD §53-54). Current position, route, next checkpoint
 * and distance only - no background tracking, no off-route detection, no offline maps.
 * Location is requested here and nowhere else (PRD §120) and is never shared publicly (BR-13).
 */
#[Layout('layouts.app')]
#[Title('Hike Mode')]
class HikeMode extends Component
{
    public TripPlan $trip;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?array $nextCheckpoint = null;

    public ?float $distanceToNextMeters = null;

    public function mount(TripPlan $trip): void
    {
        $this->authorize('view', $trip);

        $this->trip = $trip->load('trail.mountain', 'hikingSession');
    }

    /**
     * Called from the browser only while this page is open.
     */
    public function updatePosition(float $latitude, float $longitude): void
    {
        $this->authorize('update', $this->trip);

        // Nilai ini datang dari browser dan tidak boleh dipercaya begitu saja.
        Validator::make(
            ['latitude' => $latitude, 'longitude' => $longitude],
            [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]
        )->validate();

        $this->latitude = $latitude;
        $this->longitude = $longitude;

        $session = $this->trip->hikingSession;

        if ($session) {
            $session->writePoint('last_known_location', $latitude, $longitude);
            $session->update(['location_updated_at' => now()]);
        }

        $this->resolveNextCheckpoint();
    }

    /**
     * Pos berikutnya adalah pos pertama menurut urutan yang belum tercapai.
     *
     * Aturan lama, "satu setelah yang terdekat", melewati pos yang sedang dituju:
     * pendaki 100 m sebelum Pos 3 paling dekat ke Pos 3, lalu sistem menunjuk Pos 4.
     *
     * Sebuah pos dianggap tercapai setelah pendaki pernah berada dalam radius
     * kedatangannya, dan kemajuan itu diingat pada sesi sehingga tidak hilang ketika
     * pendaki turun kembali atau berputar.
     */
    private function resolveNextCheckpoint(): void
    {
        $checkpoints = $this->checkpointCoordinates();

        if ($checkpoints === [] || $this->latitude === null) {
            return;
        }

        $radius = (int) config('hiking.hike_mode.checkpoint_arrival_radius_m');
        $session = $this->trip->hikingSession;
        $reached = (int) ($session?->reached_checkpoint_sequence ?? 0);

        // Pendaki bisa membuka Hike Mode di tengah jalur, jadi pos sebelum yang terdekat
        // dianggap terlewati. Yang terdekat sendiri hanya tercapai bila masuk radius.
        $nearest = $this->nearestCheckpoint($checkpoints);

        if ($nearest !== null) {
            $reached = max($reached, (int) $nearest['sequence'] - 1);
        }

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['lat'] === null || $checkpoint['sequence'] <= $reached) {
                continue;
            }

            $distance = $this->haversineMeters($this->latitude, $this->longitude, $checkpoint['lat'], $checkpoint['lng']);

            if ($distance <= $radius) {
                // Kemajuan tidak pernah mundur, sehingga pendaki yang turun kembali
                // tidak dikembalikan ke pos yang sudah ia lewati.
                $reached = max($reached, (int) $checkpoint['sequence']);
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
            return;
        }

        $this->nextCheckpoint = $next;
        $this->distanceToNextMeters = $next['lat'] === null
            ? null
            : round($this->haversineMeters($this->latitude, $this->longitude, $next['lat'], $next['lng']));

        $session?->update([
            'reached_checkpoint_sequence' => $reached,
            'current_checkpoint_id' => $next['id'] ?? null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $checkpoints
     * @return array<string, mixed>|null
     */
    private function nearestCheckpoint(array $checkpoints): ?array
    {
        $nearest = null;
        $shortest = null;

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['lat'] === null) {
                continue;
            }

            $distance = $this->haversineMeters($this->latitude, $this->longitude, $checkpoint['lat'], $checkpoint['lng']);

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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function checkpointCoordinates(): array
    {
        // Koordinat dibaca dari kolom biasa, bukan diekstrak dari kolom geografi.
        // Sumber yang sama bekerja di mana pun, termasuk koneksi tanpa PostGIS.
        return $this->trip->trail->checkpoints
            ->map(fn (Checkpoint $checkpoint) => [
                'id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'sequence' => (int) $checkpoint->sequence,
                'lat' => $checkpoint->latitude,
                'lng' => $checkpoint->longitude,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.trips.hike-mode', [
            'checkpoints' => $this->checkpointCoordinates(),
            'geometry' => $this->trip->trail->readGeoJson('geometry'),
        ]);
    }
}
