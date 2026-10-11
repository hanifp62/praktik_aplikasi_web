<?php

namespace App\Services\Community;

use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Services\Trails\CheckpointJourney;
use App\Services\Trails\ProfilElevasiJalur;
use App\Support\Durasi;

/**
 * Hasil satu pendakian, siap dikirim ke halaman.
 *
 * Peak-end rule mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya
 * diingat. Seluruh isinya berasal dari data yang sudah tersimpan; yang tidak diketahui
 * disebut tidak tercatat, bukan diisi nol.
 */
class HasilPendakian
{
    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip): array
    {
        $trip->loadMissing('trail.mountain', 'trail.checkpoints', 'hikingSession', 'history');

        $riwayat = $trip->history;
        $jalur = $trip->trail;
        $sesi = $trip->hikingSession;

        $penandaPos = $jalur->checkpoints
            ->filter(fn (Checkpoint $p) => $p->latitude !== null && $p->longitude !== null)
            ->sortBy('sequence')
            ->map(fn (Checkpoint $p) => [
                'lng' => (float) $p->longitude,
                'lat' => (float) $p->latitude,
                'label' => $p->sequence.'. '.$p->name,
            ])
            ->values()
            ->all();

        $jejak = $this->jejak($sesi);

        return [
            'jalur' => [
                'nama' => $jalur->name,
                'slug' => $jalur->slug,
                'wilayah' => implode(' · ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            ],
            'tanggal' => $riwayat->completed_at->translatedFormat('l, d F Y'),
            'hasil' => $riwayat->completion_state->label(),
            'angka' => [
                // Null bukan nol: hike mode tidak selalu dibuka, dan "0 jam" akan menjadi
                // pernyataan yang salah tentang pendakian yang nyata.
                'durasi' => self::durasiSesi($sesi) ?? 'tidak tercatat',
                'posTerjauh' => $this->posTerjauh($trip)?->name ?? 'tidak tercatat',
                'elevasi' => $jalur->elevation_gain_m ? number_format($jalur->elevation_gain_m, 0, ',', '.').' m' : 'belum ada',
                'persiapan' => $riwayat->preparation_completion_percent.'%',
            ],
            'jejak' => $jejak === null ? null : ['geometri' => $jejak, 'sesi' => $sesi->id],
            'garisJalur' => $jalur->readGeoJson('geometry'),
            'penandaPos' => $penandaPos,
            // Bentuk yang sama dengan rincian jalur (W3): ProfilElevasi dan DaftarPos. Tanpa
            // jarak pos sepanjang garis rute dan tanpa waktu dari awal di sini: yang dicatat
            // adalah pendakian ini, bukan perkiraan.
            'profil' => ProfilElevasiJalur::untuk($jalur->elevation_profile, $jalur->checkpoints, []),
            'pos' => CheckpointJourney::daftar($jalur->checkpoints, [], []),
            'catatan' => $riwayat->personal_notes,
        ];
    }

    /**
     * Lama pendakian menurut sesi hike mode, bukan menurut tanggal rencana. Null ketika
     * hike mode tidak pernah dibuka atau tidak pernah diakhiri, dan itu keadaan yang umum.
     */
    public static function durasiSesi(?HikingSession $sesi): ?string
    {
        if ($sesi?->started_at === null || $sesi->ended_at === null) {
            return null;
        }

        return Durasi::panjang((int) round($sesi->started_at->diffInMinutes($sesi->ended_at)));
    }

    /**
     * Pos terjauh yang dicapai, dinamai alih-alih disebut sebagai angka urutan.
     *
     * Kolomnya reached_checkpoint_sequence, yang ditulis hike mode. Versi Livewire
     * membaca reached_sequence yang tidak pernah ada, sehingga pos terjauh selalu
     * tertulis "tidak tercatat" meskipun hike mode sudah mencatatnya.
     */
    private function posTerjauh(TripPlan $trip): ?Checkpoint
    {
        $urutan = $trip->hikingSession?->reached_checkpoint_sequence;

        if ($urutan === null) {
            return null;
        }

        return $trip->trail->checkpoints->firstWhere('sequence', $urutan);
    }

    /**
     * Jejak yang direkam pendaki sebagai GeoJSON LineString. Null ketika titiknya kurang
     * dari dua: satu titik bukan garis, dan menggambarnya menyatakan pendaki tidak
     * bergerak sama sekali.
     *
     * @return array{type: string, coordinates: array<int, array{0: float, 1: float}>}|null
     */
    private function jejak(?HikingSession $sesi): ?array
    {
        if ($sesi === null) {
            return null;
        }

        $titik = HikeTrackPoint::query()
            ->where('hiking_session_id', $sesi->id)
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude']);

        if ($titik->count() < 2) {
            return null;
        }

        return [
            'type' => 'LineString',
            'coordinates' => $titik
                ->map(fn (HikeTrackPoint $t) => [(float) $t->longitude, (float) $t->latitude])
                ->all(),
        ];
    }
}
