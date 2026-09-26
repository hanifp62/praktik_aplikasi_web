<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdateHikePositionRequest;
use App\Models\TripPlan;
use App\Services\CheckpointPaceService;
use App\Services\Trails\CheckpointJourney;
use App\Services\Trips\HikeCheckpointResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-15 mode pendakian, sengaja minimal (PRD §53-54): posisi sekarang, rute, pos
 * berikutnya dan jaraknya. Tanpa pelacakan latar belakang, tanpa deteksi keluar jalur,
 * tanpa peta luring. Lokasi diminta di sini dan tidak di tempat lain (PRD §120), dan
 * tidak pernah dibagikan ke publik (BR-13). Pengganti komponen HikeMode.
 *
 * Posisi hanya diambil ketika pendaki menekan "Perbarui posisi". Server menyimpan
 * posisi terakhir pada sesi (kolom privat) dan kemajuan antarpos; posisi yang baru
 * dikirim dibawa ke halaman lewat flash satu kali, tidak disimpan sebagai riwayat.
 */
class HikeModeController extends Controller
{
    public function show(Request $request, TripPlan $trip, HikeCheckpointResolver $resolver, CheckpointPaceService $pace): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'trail.checkpoints', 'hikingSession');
        $sesi = $trip->hikingSession;
        $pos = $resolver->checkpoints($trip->trail);

        /** @var array{lat: float, lng: float, pos: array{id: int, name: string, sequence: int}|null, jarak: float|null}|null $posisi */
        $posisi = session('posisi-pendakian');
        $berikutnya = $posisi !== null ? $posisi['pos'] : $resolver->current($sesi, $pos);

        return Inertia::render('Trips/Hike', [
            'title' => 'Hike Mode',
            'trip' => [
                'id' => $trip->id,
                'jalur' => $trip->trail->name,
                'gunung' => $trip->trail->mountain->name,
            ],
            'checkpoints' => array_map(fn (array $c) => [
                'id' => $c['id'],
                'name' => $c['name'],
                'sequence' => $c['sequence'],
            ], $pos),
            'penanda' => array_values(array_map(
                fn (array $c) => ['lng' => $c['lng'], 'lat' => $c['lat'], 'label' => $c['sequence'].'. '.$c['name']],
                array_filter($pos, fn (array $c) => $c['lat'] !== null && $c['lng'] !== null),
            )),
            'posBerkoordinat' => count(array_filter($pos, fn (array $c) => $c['lat'] !== null && $c['lng'] !== null)),
            'geometri' => fn () => $trip->trail->readGeoJson('geometry'),
            'posisi' => $posisi === null ? null : [
                'lat' => $posisi['lat'],
                'lng' => $posisi['lng'],
                'koordinat' => number_format($posisi['lat'], 5).', '.number_format($posisi['lng'], 5),
            ],
            'berikutnya' => $berikutnya === null ? null : $this->berikutnya($trip, $berikutnya, $posisi['jarak'] ?? null, $pace),
            // Perekaman hanya berjalan bila pendaki menyalakannya sendiri, dan hanya
            // ketika sesinya benar-benar ada. Penjagaannya ada di server juga
            // (HikeTrackController), jadi yang di sini menghindari pekerjaan sia-sia,
            // bukan menjadi satu-satunya pengaman.
            'merekamJejak' => (bool) $request->user()->preference?->record_track && $sesi !== null,
            'sesiId' => $sesi?->id,
        ]);
    }

    /**
     * Dipanggil dari peramban hanya selama halaman ini terbuka.
     */
    public function position(UpdateHikePositionRequest $request, TripPlan $trip, HikeCheckpointResolver $resolver): RedirectResponse
    {
        $lat = (float) $request->validated('latitude');
        $lng = (float) $request->validated('longitude');

        $trip->load('trail.checkpoints', 'hikingSession');
        $sesi = $trip->hikingSession;

        if ($sesi) {
            $sesi->writePoint('last_known_location', $lat, $lng);
            $sesi->update(['location_updated_at' => now()]);
        }

        $hasil = $resolver->resolve($resolver->checkpoints($trip->trail), $sesi, $lat, $lng);

        return redirect()->route('trips.hike', $trip)->with('posisi-pendakian', [
            'lat' => $lat,
            'lng' => $lng,
            'pos' => $hasil['checkpoint'] ?? null,
            'jarak' => $hasil['distanceMeters'] ?? null,
        ]);
    }

    /**
     * Pos berikutnya beserta jaraknya dari posisi terakhir yang dikirim, dan waktu tempuh
     * khas bagian itu bila rekaman pendaki sudah cukup (CheckpointPaceService). Tanpa
     * rekaman yang cukup, waktunya tidak ditaksir dengan rumus (lihat docblock service
     * itu), dan halaman mengatakannya.
     *
     * @param  array{id: int, name: string, sequence: int}  $pos
     * @return array<string, mixed>
     */
    private function berikutnya(TripPlan $trip, array $pos, ?float $jarak, CheckpointPaceService $pace): array
    {
        $perjalanan = collect(CheckpointJourney::susun($trip->trail->checkpoints, $pace->forTrail($trip->trail)))
            ->firstWhere('id', $pos['id']);

        return [
            'id' => $pos['id'],
            'name' => $pos['name'],
            'sequence' => $pos['sequence'],
            'jarak' => $jarak === null ? null : ($jarak >= 1000
                ? number_format($jarak / 1000, 2, ',', '.').' km'
                : number_format($jarak, 0, ',', '.').' m'),
            'antara' => $perjalanan['antara'] ?? null,
            'tempo' => $perjalanan['tempo'] ?? null,
        ];
    }
}
