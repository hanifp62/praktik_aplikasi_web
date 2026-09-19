<?php

namespace App\Livewire\Trips;

use App\Models\Checkpoint;
use App\Models\TripPlan;
use Illuminate\Support\Facades\DB;
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

        $this->latitude = $latitude;
        $this->longitude = $longitude;

        $session = $this->trip->hikingSession;

        if ($session) {
            $session->writePoint('last_known_location', $latitude, $longitude);
            $session->update(['location_updated_at' => now()]);
        }

        $this->resolveNextCheckpoint();
    }

    private function resolveNextCheckpoint(): void
    {
        $checkpoints = $this->checkpointCoordinates();

        if ($checkpoints === [] || $this->latitude === null) {
            return;
        }

        $nearestIndex = null;
        $nearestDistance = null;

        foreach ($checkpoints as $index => $checkpoint) {
            if ($checkpoint['lat'] === null) {
                continue;
            }

            $distance = $this->haversineMeters($this->latitude, $this->longitude, $checkpoint['lat'], $checkpoint['lng']);

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestIndex = $index;
            }
        }

        if ($nearestIndex === null) {
            return;
        }

        // The next checkpoint is the one after the closest, falling back to the closest at the end.
        $next = $checkpoints[$nearestIndex + 1] ?? $checkpoints[$nearestIndex];

        $this->nextCheckpoint = $next;
        $this->distanceToNextMeters = $next['lat'] === null
            ? null
            : round($this->haversineMeters($this->latitude, $this->longitude, $next['lat'], $next['lng']));

        if (isset($next['id'])) {
            $this->trip->hikingSession?->update(['current_checkpoint_id' => $next['id']]);
        }
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
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
        if (! Checkpoint::spatialSupported()) {
            return $this->trip->trail->checkpoints
                ->map(fn (Checkpoint $checkpoint) => [
                    'id' => $checkpoint->id,
                    'name' => $checkpoint->name,
                    'sequence' => $checkpoint->sequence,
                    'lat' => null,
                    'lng' => null,
                ])->all();
        }

        return DB::table('checkpoints')
            ->where('trail_id', $this->trip->trail_id)
            ->orderBy('sequence')
            ->selectRaw('id, name, sequence, ST_Y(location::geometry) as lat, ST_X(location::geometry) as lng')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'sequence' => $row->sequence,
                'lat' => $row->lat !== null ? (float) $row->lat : null,
                'lng' => $row->lng !== null ? (float) $row->lng : null,
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
