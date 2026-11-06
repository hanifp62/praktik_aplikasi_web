<?php

namespace App\Services;

use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Trail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Waktu tempuh khas antarpos, dihitung dari jejak yang direkam pendaki.
 *
 * Ini adaptasi segmen Strava yang dijanjikan spec: strukturnya dipakai, perlombaannya
 * tidak. Yang dihasilkan bukan peringkat siapa tercepat, melainkan keterangan yang
 * dibutuhkan saat merencanakan, yaitu berapa lama bagian ini biasanya ditempuh.
 *
 * ## Mengapa bukan rumus
 *
 * Naismith dan turunannya adalah rujukan paling mapan untuk menaksir waktu pendakian,
 * dan keduanya tidak dipakai di sini. Naismith dikalibrasi pada fells dan pegunungan
 * Inggris dan Skotlandia, dan tidak pernah dievaluasi di luar kondisi itu. Gunung api
 * tropis Indonesia, dengan pasir vulkanik, kelembapan, hutan rapat, dan kemiringan yang
 * jauh lebih curam, persis kondisi yang belum pernah divalidasi. Literatur yang sama
 * juga menyebut taksiran per-segmen sebagai bagian paling lemah dari rumus semacam itu,
 * dan per-segmen justru yang dibutuhkan di sini.
 *
 * ## Mengapa rentang, dan mengapa lima
 *
 * Waktu tempuh pendaki miring ke kanan: yang lambat punya ekor panjang, sehingga
 * rata-rata tertarik ke atas oleh sedikit orang. Median tidak.
 *
 * Yang ditampilkan rentang teramati, bukan satu angka, karena rentang itulah yang
 * cakupannya dapat dinyatakan: untuk sampel berukuran n, peluang rentang [min, maks]
 * memuat median populasi adalah 1 - 2(1/2)^n. Pada n=5 peluangnya 93,75 persen, pada
 * n=4 baru 87,5 persen. Lima karena itu ambang terkecil yang angkanya layak disebut,
 * dan angkanya berasal dari hitungan, bukan dari selera.
 */
class CheckpointPaceService
{
    /**
     * Lima rekaman, dari 1 - 2(1/2)^5 = 93,75 persen.
     */
    public const MINIMUM_REKAMAN = 5;

    /**
     * Jarak sebuah jejak dianggap melewati pos, dalam meter.
     *
     * Pendaki melewati pos, bukan menginjak titik koordinatnya, dan ketelitian GPS di
     * bawah tajuk hutan mudah meleset puluhan meter. Radius yang terlalu rapat membuang
     * rekaman yang sah; yang terlalu longgar menyatukan dua pos yang berdekatan.
     */
    private const RADIUS_POS_M = 60;

    /**
     * Sesi terbaru yang ikut dihitung. Batas ini yang menjaga biayanya tetap terikat
     * ketika sebuah jalur ramai.
     */
    private const MAKS_SESI = 50;

    /**
     * Waktu tempuh khas antarpos berurutan pada satu jalur.
     *
     * @return array<int, array{dari: int, ke: int, rekaman: int, median_menit: int, min_menit: int, maks_menit: int}>
     */
    public function forTrail(Trail $trail): array
    {
        return Cache::remember(
            "trail:{$trail->id}:checkpoint-pace",
            (int) config('hiking.cache.public_ttl_seconds'),
            fn () => $this->hitung($trail)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hitung(Trail $trail): array
    {
        $pos = $trail->checkpoints
            ->filter(fn (Checkpoint $p) => $p->latitude !== null && $p->longitude !== null)
            ->sortBy('sequence')
            ->values();

        if ($pos->count() < 2) {
            return [];
        }

        $sesi = HikingSession::query()
            ->whereHas('tripPlan', fn ($q) => $q->where('trail_id', $trail->id))
            ->latest('started_at')
            ->limit(self::MAKS_SESI)
            ->pluck('id');

        if ($sesi->isEmpty()) {
            return [];
        }

        $kedatangan = $this->kedatanganPerSesi($sesi, $pos);
        $hasil = [];

        for ($i = 1; $i < $pos->count(); $i++) {
            $durasi = $this->durasiAntara($kedatangan, $pos[$i - 1]->id, $pos[$i]->id);

            if ($durasi->count() < self::MINIMUM_REKAMAN) {
                continue;
            }

            $hasil[] = [
                'dari' => $pos[$i - 1]->id,
                'ke' => $pos[$i]->id,
                'rekaman' => $durasi->count(),
                'median_menit' => (int) round($this->median($durasi)),
                'min_menit' => (int) round($durasi->min()),
                'maks_menit' => (int) round($durasi->max()),
            ];
        }

        return $hasil;
    }

    /**
     * Waktu kedatangan pertama tiap sesi di tiap pos.
     *
     * Kedatangan pertama, bukan terakhir: jejak pendakian melewati pos yang sama dua
     * kali, sekali naik dan sekali turun, dan yang dibicarakan halaman ini perjalanan
     * naiknya.
     *
     * @param  Collection<int, int>  $sesi
     * @param  Collection<int, Checkpoint>  $pos
     * @return array<int, array<int, float>> [sesi_id][pos_id] => timestamp
     */
    private function kedatanganPerSesi(Collection $sesi, Collection $pos): array
    {
        $kedatangan = [];

        HikeTrackPoint::query()
            ->whereIn('hiking_session_id', $sesi)
            ->orderBy('recorded_at')
            ->chunk(2000, function ($titik) use (&$kedatangan, $pos) {
                foreach ($titik as $t) {
                    foreach ($pos as $p) {
                        if (isset($kedatangan[$t->hiking_session_id][$p->id])) {
                            continue;
                        }

                        if ($this->meter($t->latitude, $t->longitude, (float) $p->latitude, (float) $p->longitude) <= self::RADIUS_POS_M) {
                            $kedatangan[$t->hiking_session_id][$p->id] = $t->recorded_at->getTimestamp();
                        }
                    }
                }
            });

        return $kedatangan;
    }

    /**
     * @param  array<int, array<int, float>>  $kedatangan
     * @return Collection<int, float> durasi dalam menit
     */
    private function durasiAntara(array $kedatangan, int $dari, int $ke): Collection
    {
        $durasi = collect();

        foreach ($kedatangan as $pos) {
            if (! isset($pos[$dari], $pos[$ke])) {
                continue;
            }

            $menit = ($pos[$ke] - $pos[$dari]) / 60;

            // Kedatangan di pos berikutnya harus sesudah, bukan sebelum. Urutan terbalik
            // berarti jejaknya menuruni jalur, dan itu perjalanan yang berbeda.
            if ($menit > 0) {
                $durasi->push($menit);
            }
        }

        return $durasi;
    }

    /**
     * @param  Collection<int, float>  $nilai
     */
    private function median(Collection $nilai): float
    {
        $urut = $nilai->sort()->values();
        $tengah = intdiv($urut->count(), 2);

        return $urut->count() % 2 === 1
            ? $urut[$tengah]
            : ($urut[$tengah - 1] + $urut[$tengah]) / 2;
    }

    /**
     * Haversine, rumus yang sama dengan mode pendakian dan GpxTrack, supaya tidak ada
     * jawaban kedua untuk pertanyaan yang sama.
     */
    private function meter(float $latA, float $lonA, float $latB, float $lonB): float
    {
        $radius = (int) config('hiking.hike_mode.earth_radius_m', 6371000);

        $dLat = deg2rad($latB - $latA);
        $dLon = deg2rad($lonB - $lonA);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($dLon / 2) ** 2;

        return $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function forgetCached(int $trailId): void
    {
        Cache::forget("trail:{$trailId}:checkpoint-pace");
    }
}
