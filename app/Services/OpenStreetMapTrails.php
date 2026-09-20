<?php

namespace App\Services;

use App\Support\GpxTrack;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Mencari kandidat geometri jalur di OpenStreetMap untuk dipilih kurator (PRD §109).
 *
 * Sengaja berhenti di "kandidat". Tidak ada cara program memastikan sebuah way di OSM
 * adalah jalur yang kita namai "Jalur Selo" dan bukan jalan setapak kebun di sebelahnya.
 * Pencarian nama malah membuktikan bahayanya: "Gunung Prau" di Nominatim mengembalikan
 * bukit di Ponorogo, bukan Prau di Dieng. Jalur yang salah menempatkan pendaki di gunung
 * yang salah, jadi keputusannya tetap di tangan manusia.
 *
 * Data OSM adalah data komunitas, bukan data resmi pengelola. Sumbernya dicatat apa
 * adanya supaya §60 dan §92 tetap terjaga.
 */
class OpenStreetMapTrails
{
    public const ENDPOINT = 'https://overpass-api.de/api/interpreter';

    public const SOURCE_NAME = 'OpenStreetMap';

    /**
     * Potongan sependek ini hampir selalu penggal jalan, bukan jalur pendakian.
     */
    private const MINIMAL_TITIK = 10;

    /**
     * @return array<int, array{id: int, nama: string, titik: int, panjang_km: float, koordinat: array<int, array{0: float, 1: float}>}>
     */
    public function near(float $latitude, float $longitude, int $radiusMeter = 5000): array
    {
        $elemen = $this->ambil($latitude, $longitude, $radiusMeter);

        $kandidat = [];

        foreach ($elemen as $way) {
            $koordinat = $this->koordinat($way);

            if (count($koordinat) < self::MINIMAL_TITIK) {
                continue;
            }

            $kandidat[] = [
                'id' => (int) $way['id'],
                'nama' => $way['tags']['name'] ?? 'Tanpa nama di OSM',
                'titik' => count($koordinat),
                'panjang_km' => round(GpxTrack::lengthKm($koordinat), 2),
                'koordinat' => $koordinat,
            ];
        }

        // Yang terpanjang lebih dulu: jalur pendakian hampir selalu lebih panjang
        // daripada potongan jalan di sekitarnya.
        usort($kandidat, fn (array $a, array $b) => $b['panjang_km'] <=> $a['panjang_km']);

        return $kandidat;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ambil(float $latitude, float $longitude, int $radiusMeter): array
    {
        $kueri = sprintf(
            '[out:json][timeout:60];(way["highway"~"^(path|footway|track)$"](around:%d,%F,%F););out geom;',
            $radiusMeter,
            $latitude,
            $longitude
        );

        try {
            $respons = Http::timeout(60)
                // Overpass menolak permintaan tanpa User-Agent dengan 406, dan kegagalan
                // itu mudah salah dibaca sebagai "tidak ada data di sana".
                ->withHeaders(['User-Agent' => config('app.name').' (kurasi data jalur)'])
                ->asForm()
                ->post(self::ENDPOINT, ['data' => $kueri]);

            if ($respons->failed()) {
                return [];
            }

            return $respons->json('elements') ?? [];
        } catch (Throwable $e) {
            // §94: kegagalan sumber luar tidak menjatuhkan alur. Kurator tetap dapat
            // mengunggah GPX.
            report($e);

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $way
     * @return array<int, array{0: float, 1: float}>
     */
    private function koordinat(array $way): array
    {
        $titik = [];

        foreach ($way['geometry'] ?? [] as $simpul) {
            if (isset($simpul['lat'], $simpul['lon'])) {
                // Bujur dulu, mengikuti GeoJSON dan writeLineString().
                $titik[] = [(float) $simpul['lon'], (float) $simpul['lat']];
            }
        }

        return $titik;
    }
}
