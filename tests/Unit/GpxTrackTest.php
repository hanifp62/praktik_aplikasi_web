<?php

namespace Tests\Unit;

use App\Support\GpxTrack;
use RuntimeException;
use Tests\TestCase;

/**
 * writeLineString() sudah ada di model sejak awal tetapi tidak pernah dipanggil dari
 * mana pun, sehingga kurator tidak punya cara memasukkan geometri jalur selain menulis
 * SQL sendiri. Akibatnya nol dari tujuh jalur punya geometri, peta tidak dapat
 * menggambar jalur, dan gerbang §110 menolak semuanya.
 *
 * Parser dipisah dari komponen Livewire supaya dapat diuji tanpa PostGIS.
 */
class GpxTrackTest extends TestCase
{
    public function test_it_reads_track_points_in_order(): void
    {
        $koordinat = GpxTrack::fromXml($this->gpx([
            [-7.9970, 112.9500],
            [-7.9950, 112.9505],
            [-7.9930, 112.9510],
        ]));

        $this->assertCount(3, $koordinat);
        $this->assertEqualsWithDelta(112.9500, $koordinat[0][0], 0.000001);
        $this->assertEqualsWithDelta(-7.9970, $koordinat[0][1], 0.000001);
        $this->assertEqualsWithDelta(-7.9930, $koordinat[2][1], 0.000001);
    }

    public function test_it_returns_longitude_first_to_match_geojson(): void
    {
        [$pertama] = GpxTrack::fromXml($this->gpx([[-7.9970, 112.9500], [-7.9950, 112.9505]]));

        $this->assertGreaterThan(100, $pertama[0], 'Bujur harus di indeks 0.');
        $this->assertLessThan(0, $pertama[1], 'Lintang harus di indeks 1.');
    }

    public function test_it_joins_several_track_segments(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0"?>
        <gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">
          <trk><trkseg>
            <trkpt lat="-7.9970" lon="112.9500"/>
            <trkpt lat="-7.9950" lon="112.9505"/>
          </trkseg>
          <trkseg>
            <trkpt lat="-7.9930" lon="112.9510"/>
          </trkseg></trk>
        </gpx>
        XML;

        $this->assertCount(3, GpxTrack::fromXml($xml));
    }

    public function test_it_falls_back_to_route_points(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0"?>
        <gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">
          <rte>
            <rtept lat="-7.9970" lon="112.9500"/>
            <rtept lat="-7.9950" lon="112.9505"/>
          </rte>
        </gpx>
        XML;

        $this->assertCount(2, GpxTrack::fromXml($xml));
    }

    public function test_it_ignores_elevation(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0"?>
        <gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">
          <trk><trkseg>
            <trkpt lat="-7.9970" lon="112.9500"><ele>2100.5</ele></trkpt>
            <trkpt lat="-7.9950" lon="112.9505"><ele>2210.0</ele></trkpt>
          </trkseg></trk>
        </gpx>
        XML;

        $koordinat = GpxTrack::fromXml($xml);

        $this->assertCount(2, $koordinat[0]);
    }

    public function test_it_rejects_a_file_that_is_not_xml(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bukan berkas GPX');

        GpxTrack::fromXml('ini bukan xml sama sekali');
    }

    public function test_it_rejects_xml_that_is_not_gpx(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bukan berkas GPX');

        GpxTrack::fromXml('<?xml version="1.0"?><catatan><isi>halo</isi></catatan>');
    }

    public function test_it_rejects_a_gpx_without_track_points(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak memuat titik jalur');

        GpxTrack::fromXml('<?xml version="1.0"?><gpx version="1.1"><wpt lat="-7.99" lon="112.95"/></gpx>');
    }

    public function test_a_single_point_is_not_a_track(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('minimal dua titik');

        GpxTrack::fromXml($this->gpx([[-7.9970, 112.9500]]));
    }

    public function test_it_rejects_coordinates_outside_the_valid_range(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('di luar rentang');

        GpxTrack::fromXml($this->gpx([[-7.9970, 112.9500], [-91.0, 112.9505]]));
    }

    /**
     * Jejak yang terlalu rapat ditolak, bukan diencerkan diam-diam. Menghapus titik dari
     * jalur pendakian berarti memotong tikungan pada fitur navigasi lapangan, dan kurator
     * yang memilih ketelitiannya, bukan aplikasi.
     */
    public function test_it_rejects_a_track_with_too_many_points(): void
    {
        $titik = array_fill(0, 12, [-7.99, 112.95]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('terlalu banyak titik');

        GpxTrack::fromXml($this->gpx($titik), maxPoints: 10);
    }

    /**
     * Panjang jejak adalah pemeriksaan silang kurator terhadap jarak yang sudah tercatat
     * pada jalur. Selisih yang besar biasanya berarti berkas GPX milik jalur lain, dan
     * itu kesalahan yang menempatkan pendaki di gunung yang salah.
     */
    public function test_it_measures_the_track_length(): void
    {
        // Satu derajat bujur di khatulistiwa kira-kira 111,3 km.
        $panjang = GpxTrack::lengthKm([[112.0, 0.0], [113.0, 0.0]]);

        $this->assertEqualsWithDelta(111.3, $panjang, 0.5);
    }

    public function test_a_track_length_adds_up_over_several_segments(): void
    {
        $satu = GpxTrack::lengthKm([[112.0, 0.0], [112.5, 0.0]]);
        $dua = GpxTrack::lengthKm([[112.0, 0.0], [112.5, 0.0], [113.0, 0.0]]);

        $this->assertEqualsWithDelta($satu * 2, $dua, 0.01);
    }

    public function test_a_track_shorter_than_two_points_has_no_length(): void
    {
        $this->assertSame(0.0, GpxTrack::lengthKm([[112.0, 0.0]]));
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik  pasangan [lintang, bujur]
     */
    private function gpx(array $titik): string
    {
        $baris = implode("\n", array_map(
            fn (array $t) => sprintf('<trkpt lat="%F" lon="%F"/>', $t[0], $t[1]),
            $titik
        ));

        return '<?xml version="1.0"?><gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">'
            ."<trk><trkseg>{$baris}</trkseg></trk></gpx>";
    }
}
