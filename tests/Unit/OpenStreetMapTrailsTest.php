<?php

namespace Tests\Unit;

use App\Services\OpenStreetMapTrails;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Mencari kandidat geometri jalur di OpenStreetMap untuk dipilih kurator.
 *
 * Sengaja tidak otomatis. Tidak ada cara program memastikan sebuah way di OSM adalah
 * "Jalur Selo" dan bukan jalan setapak kebun di sebelahnya, dan pencarian nama justru
 * membuktikan bahayanya: "Gunung Prau" di Nominatim mengembalikan bukit di Ponorogo,
 * bukan Prau di Dieng. Salah jalur berarti menempatkan pendaki di gunung yang salah.
 *
 * Maka layanan ini hanya menyajikan kandidat beserta ukurannya. Manusia yang memutuskan.
 */
class OpenStreetMapTrailsTest extends TestCase
{
    public function test_it_returns_candidates_with_what_a_curator_needs_to_choose(): void
    {
        Http::fake([
            '*' => Http::response($this->jawaban([
                $this->way(101, 'Jalur Pendakian Selo', $this->garis(24)),
            ])),
        ]);

        $kandidat = app(OpenStreetMapTrails::class)->near(-7.45, 110.44);

        $this->assertCount(1, $kandidat);
        $this->assertSame(101, $kandidat[0]['id']);
        $this->assertSame('Jalur Pendakian Selo', $kandidat[0]['nama']);
        $this->assertSame(24, $kandidat[0]['titik']);
        $this->assertGreaterThan(0, $kandidat[0]['panjang_km']);
    }

    public function test_coordinates_come_back_in_geojson_order(): void
    {
        Http::fake(['*' => Http::response($this->jawaban([
            $this->way(1, 'Uji', $this->garis(15)),
        ]))]);

        [$pertama] = app(OpenStreetMapTrails::class)->near(-7.45, 110.44)[0]['koordinat'];

        $this->assertEqualsWithDelta(110.44, $pertama[0], 0.0001, 'Bujur di indeks 0.');
        $this->assertEqualsWithDelta(-7.45, $pertama[1], 0.0001, 'Lintang di indeks 1.');
    }

    /**
     * Potongan sependek dua titik hampir selalu penggal jalan, bukan jalur pendakian.
     * Menyodorkannya sebagai kandidat hanya menambah pekerjaan memilah.
     */
    public function test_it_drops_fragments_too_short_to_be_a_trail(): void
    {
        Http::fake(['*' => Http::response($this->jawaban([
            $this->way(1, 'Penggal', [[110.44, -7.45], [110.4401, -7.4501]]),
            $this->way(2, 'Jalur panjang', $this->garis(40)),
        ]))]);

        $kandidat = app(OpenStreetMapTrails::class)->near(-7.45, 110.44);

        $this->assertCount(1, $kandidat);
        $this->assertSame(2, $kandidat[0]['id']);
    }

    public function test_the_longest_candidate_comes_first(): void
    {
        Http::fake(['*' => Http::response($this->jawaban([
            $this->way(1, 'Pendek', $this->garis(30)),
            $this->way(2, 'Panjang', $this->garis(120)),
        ]))]);

        $kandidat = app(OpenStreetMapTrails::class)->near(-7.45, 110.44);

        $this->assertSame(2, $kandidat[0]['id']);
    }

    public function test_an_unnamed_way_is_labelled_honestly(): void
    {
        Http::fake(['*' => Http::response($this->jawaban([
            ['type' => 'way', 'id' => 7, 'tags' => ['highway' => 'path'], 'geometry' => $this->titik($this->garis(30))],
        ]))]);

        $this->assertSame('Tanpa nama di OSM', app(OpenStreetMapTrails::class)->near(-7.45, 110.44)[0]['nama']);
    }

    /**
     * §94: kegagalan sumber luar tidak boleh menjatuhkan alur. Kurator tetap dapat
     * mengunggah GPX.
     */
    public function test_a_failing_service_returns_nothing_instead_of_throwing(): void
    {
        Http::fake(['*' => Http::response('gagal', 500)]);

        $this->assertSame([], app(OpenStreetMapTrails::class)->near(-7.45, 110.44));
    }

    public function test_a_timeout_returns_nothing_instead_of_throwing(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('putus')]);

        $this->assertSame([], app(OpenStreetMapTrails::class)->near(-7.45, 110.44));
    }

    /**
     * Overpass menolak permintaan tanpa User-Agent dengan 406, dan kegagalan itu mudah
     * salah dibaca sebagai "tidak ada data di sana".
     */
    public function test_it_identifies_itself_to_the_service(): void
    {
        Http::fake(['*' => Http::response($this->jawaban([]))]);

        app(OpenStreetMapTrails::class)->near(-7.45, 110.44);

        Http::assertSent(fn ($request) => filled($request->header('User-Agent')[0] ?? null));
    }

    /**
     * @param  array<int, array<string, mixed>>  $elemen
     */
    private function jawaban(array $elemen): array
    {
        return ['elements' => $elemen];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $koordinat
     * @return array<string, mixed>
     */
    private function way(int $id, string $nama, array $koordinat): array
    {
        return [
            'type' => 'way',
            'id' => $id,
            'tags' => ['highway' => 'path', 'name' => $nama],
            'geometry' => $this->titik($koordinat),
        ];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $koordinat
     * @return array<int, array{lat: float, lon: float}>
     */
    private function titik(array $koordinat): array
    {
        return array_map(fn (array $k) => ['lat' => $k[1], 'lon' => $k[0]], $koordinat);
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private function garis(int $jumlah): array
    {
        $titik = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $titik[] = [110.44 + $i * 0.001, -7.45 - $i * 0.001];
        }

        return $titik;
    }
}
