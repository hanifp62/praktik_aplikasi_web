<?php

namespace App\Services\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\CheckpointPaceService;
use App\Services\Trails\CheckpointJourney;
use App\Services\Trails\SkalaProfil;
use Illuminate\Support\Collection;

/**
 * Data mode lapangan satu trip (spec v3 bagian 6.12, prototype 11; rencana induk W6 butir 2).
 *
 * Server hanya mengirim data jalur yang stabil dan keadaan sesi: pos berurutan dengan
 * koordinatnya, garis jalur, profil elevasi, perkiraan waktu per ruas dari rekaman pendaki,
 * laporan komunitas per segmen, dan kemajuan yang sudah tercatat. Posisi, sisa jarak, naik
 * lagi, dan tiba di pos dihitung di perangkat (resources/js/components/mendaki/hitung.ts),
 * jadi halaman ini tetap berguna tanpa sinyal. Posisi pendaki tidak dikirim ke server dari mode
 * lapangan. Titik jejak, bila rekam jejak menyala atas persetujuan di Profil, disimpan di
 * perangkat dan dikirim sesudah pendakian selesai atau dibatalkan, dari halaman aplikasi lain
 * (ruling W6-7f, W6-P2). urls.jejak tetap dibawa supaya bentuk data dan hash paket lapangan tidak
 * berubah; perekam tidak memakainya lagi.
 *
 * Bentuk yang sama disimpan paket lapangan luring, sehingga mode lapangan adalah satu pohon
 * komponen dengan dua sumber data (spec v3 bagian 10.4).
 */
final class HalamanMendaki
{
    /** Profil yang lebih rapat dari ini dijarangkan: cukup untuk menghitung naik lagi per ruas. */
    private const MAKS_TITIK_PROFIL = 400;

    public function __construct(
        private readonly CheckpointPaceService $pace,
        private readonly KartuDarurat $darurat,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain', 'trail.checkpoints', 'hikingSession');

        $jalur = $trip->trail;
        $sesi = $trip->hikingSession;
        $pos = $jalur->checkpoints->sortBy('sequence')->values();
        $terakhir = $pos->last();
        $garis = $jalur->readGeoJson('geometry');

        return [
            'trip' => [
                'id' => $trip->id,
                'nama' => $trip->name,
                'jalur' => $jalur->name,
                'gunung' => $jalur->mountain->name,
                'berlangsung' => $trip->status === TripStatus::IN_PROGRESS && $sesi !== null,
                'mulai' => $sesi?->started_at?->utc()->toIso8601String(),
                'jedaSejak' => $sesi?->paused_at?->utc()->toIso8601String(),
                'jedaDetik' => (int) ($sesi?->paused_seconds ?? 0),
            ],
            'pos' => $pos->map(fn (Checkpoint $p) => [
                'id' => $p->id,
                'urutan' => (int) $p->sequence,
                'nama' => $p->name,
                'jenis' => $p->checkpoint_type->label(),
                'aksen' => CheckpointJourney::aksen($p, $terakhir),
                'lng' => $p->longitude === null ? null : (float) $p->longitude,
                'lat' => $p->latitude === null ? null : (float) $p->latitude,
                'elevasi' => $p->elevation_m,
                'segmen' => $p->trail_segment_id,
            ])->all(),
            'garis' => ($garis['type'] ?? null) === 'LineString' ? $garis['coordinates'] : null,
            'profil' => self::profil($jalur->elevation_profile),
            'ruas' => $this->ruas($jalur, $pos),
            'tercapai' => (int) ($sesi?->reached_checkpoint_sequence ?? 0),
            'radiusTiba' => (int) config('hiking.hike_mode.checkpoint_arrival_radius_m'),
            'laporan' => $this->laporan($jalur),
            'rekamJejak' => (bool) $pengguna->preference?->record_track && $sesi !== null,
            'sesiId' => $sesi?->id,
            'sumber' => 'daring',
            // Kartu darurat (ruling W6-R4): null selama alat keselamatan menunggu Q14.
            'darurat' => config('hiking.field_safety.enabled') ? $this->darurat->untuk($jalur) : null,
            // Lembar Selesai (DK-50): hasil pendakian dan alasan berbalik, dari enum yang sama
            // dengan validasi server.
            'pilihan' => [
                'hasil' => array_map(fn (CompletionState $s) => ['value' => $s->value, 'label' => $s->label()], CompletionState::cases()),
                'alasan' => CancellationReason::pilihan(),
            ],
            // Relatif (ruling W6-8l): bentuk yang sama disimpan paket lapangan, jadi versi paket
            // tidak bergantung pada skema dan host permintaan, dan aksi antrean dari shell luring
            // tidak membawa http:// dari balik proxy TLS. aksiSah dan kirimAksi memakai string apa adanya.
            'urls' => [
                'tiba' => route('trips.hike.arrive', $trip, absolute: false),
                'jeda' => route('trips.hike.pause', $trip, absolute: false),
                'lanjut' => route('trips.hike.resume', $trip, absolute: false),
                'selesai' => route('trips.complete', $trip, absolute: false),
                'ruangKerja' => route('trips.show', $trip, absolute: false),
                'jejak' => $sesi !== null ? route('hike.track', $sesi, absolute: false) : null,
            ],
        ];
    }

    /**
     * Perkiraan waktu satu ruas sebagai rentang teramati (DK-31): menit sampai dua jam, lalu
     * jam berkelipatan setengah. Tanda pisah pendek untuk rentang di data (spec v3 bagian 8.8).
     */
    public static function perkiraan(int $tercepat, int $terlama): string
    {
        if ($terlama <= 120) {
            return $tercepat === $terlama ? $tercepat.' menit' : $tercepat.'–'.$terlama.' menit';
        }

        $jam = fn (int $menit) => rtrim(rtrim(number_format(round($menit / 30) / 2, 1, ',', '.'), '0'), ',');

        return $jam($tercepat) === $jam($terlama) ? $jam($tercepat).' jam' : $jam($tercepat).'–'.$jam($terlama).' jam';
    }

    /**
     * Titik profil [km, m] yang sah, dijarangkan bila terlalu rapat; null bila tidak ada profil
     * (R-007): naik lagi tidak ditebak dari ketinggian GPS.
     *
     * @param  array<int, mixed>|null  $profil
     * @return array<int, array{0: float, 1: float}>|null
     */
    private static function profil(?array $profil): ?array
    {
        $titik = SkalaProfil::titik($profil);

        if ($titik === null) {
            return null;
        }

        $langkah = (int) ceil(count($titik) / self::MAKS_TITIK_PROFIL);

        return array_values(array_filter($titik, fn (array $t, int $i) => $i % $langkah === 0 || $i === count($titik) - 1, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Perkiraan waktu per ruas, dari rekaman pendaki (CheckpointPaceService, paling sedikit lima
     * rekaman per ruas). Ruas tanpa rekaman yang cukup bernilai null; perangkat menulis "belum
     * ada data waktu tempuh", tidak menaksir dengan rumus.
     *
     * @param  Collection<int, Checkpoint>  $pos
     * @return array<int, array{ke: int, perkiraan: string|null, rekaman: int|null}>
     */
    private function ruas(Trail $jalur, Collection $pos): array
    {
        $tempo = collect($this->pace->forTrail($jalur))->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);
        $hasil = [];

        for ($i = 1; $i < $pos->count(); $i++) {
            $t = $tempo->get($pos[$i - 1]->id.'-'.$pos[$i]->id);

            $hasil[] = [
                'ke' => (int) $pos[$i]->sequence,
                'perkiraan' => $t === null ? null : self::perkiraan((int) $t['min_menit'], (int) $t['maks_menit']),
                'rekaman' => $t === null ? null : (int) $t['rekaman'],
            ];
        }

        return $hasil;
    }

    /**
     * Laporan komunitas yang lolos moderasi dalam jendela terbaru, per segmen (R-010): tampil
     * sebagai laporan pendaki, tidak pernah sebagai status jalur, dan tanpa nama pelapor.
     * Batasnya hiking.conditions.community_report_limit, satu sumber dengan paket lapangan
     * luring (PaketLapangan; pra-tinjau Tugas 8 PT8-12 bagian c).
     *
     * @return array<int, array{segmen: int|null, kondisi: string, catatan: string|null, waktu: string}>
     */
    private function laporan(Trail $jalur): array
    {
        return TrailConditionReport::query()
            ->where('trail_id', $jalur->id)
            ->visibleToPublic()
            ->dalamJendelaTerbaru()
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->limit((int) config('hiking.conditions.community_report_limit'))
            ->get(['id', 'trail_segment_id', 'condition_tags', 'note', 'hike_date'])
            ->map(fn (TrailConditionReport $l) => [
                'segmen' => $l->trail_segment_id,
                'kondisi' => implode(', ', array_map(fn ($tag) => $tag->label(), $l->tags())),
                'catatan' => $l->note,
                'waktu' => match ($hari = $l->daysSinceHike()) {
                    0 => 'hari ini',
                    1 => 'kemarin',
                    default => $hari.' hari lalu',
                },
            ])
            ->all();
    }
}
