<?php

namespace Tests\Feature;

use App\Support\GpxTrack;
use Tests\TestCase;

/**
 * Ketinggian dari berkas GPX.
 *
 * Sebelum ini tag ele diabaikan seluruhnya, padahal hampir setiap berkas dari perangkat
 * GPS membawanya. Elevation gain karena itu hanya bisa diketik tangan, sementara angkanya
 * sudah ada di dalam berkas yang sedang diunggah.
 *
 * Yang paling menentukan benar-salahnya bukan penguraian XML-nya, melainkan cara
 * menjumlahkan tanjakan, jadi itu yang diuji terhadap angka yang dihitung tangan.
 */
class GpxElevationTest extends TestCase
{
    /**
     * @param  array<int, array{0: float, 1: float, 2: float|null}>  $titik
     */
    private function gpx(array $titik): string
    {
        $baris = '';

        foreach ($titik as [$lintang, $bujur, $ele]) {
            $baris .= sprintf(
                '<trkpt lat="%F" lon="%F">%s</trkpt>',
                $lintang,
                $bujur,
                $ele === null ? '' : '<ele>'.$ele.'</ele>'
            );
        }

        return '<?xml version="1.0"?><gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1">'
            .'<trk><trkseg>'.$baris.'</trkseg></trk></gpx>';
    }

    /**
     * @param  array<int, float|null>  $ketinggian
     * @return array<int, array{0: float, 1: float, 2: float|null}>
     */
    private function jejakDengan(array $ketinggian): array
    {
        $titik = [];

        foreach ($ketinggian as $i => $m) {
            $titik[] = [-8.10 + $i * 0.001, 112.92 + $i * 0.001, $m];
        }

        return $titik;
    }

    public function test_elevation_is_read_alongside_the_coordinates(): void
    {
        $jejak = GpxTrack::trackFromXml($this->gpx($this->jejakDengan([1200.0, 1250.5, 1300.0])));

        $this->assertCount(3, $jejak['coordinates']);
        $this->assertSame([1200.0, 1250.5, 1300.0], $jejak['elevations']);
    }

    /**
     * Penjumlahan naif mengubah derau menjadi tanjakan. Deret ini murni derau tiga
     * meter di sekitar satu ketinggian, dan seorang pendaki yang berdiri diam di
     * situ tidak mendaki satu meter pun.
     */
    public function test_gps_noise_does_not_become_a_climb(): void
    {
        $hasil = GpxTrack::gainLoss([1000.0, 1003.0, 1000.0, 1003.0, 1000.0, 1003.0, 1000.0]);

        $this->assertSame(['gain' => 0, 'loss' => 0], $hasil);
    }

    /**
     * Tanjakan sungguhan tetap terhitung utuh, termasuk turunan di tengahnya yang
     * memang harus didaki ulang.
     */
    public function test_a_real_climb_is_counted_in_full(): void
    {
        $hasil = GpxTrack::gainLoss([1000.0, 1100.0, 1050.0, 1200.0]);

        $this->assertSame(['gain' => 250, 'loss' => 50], $hasil);
    }

    public function test_the_threshold_is_adjustable_and_actually_changes_the_answer(): void
    {
        $deret = [1000.0, 1004.0, 1000.0, 1004.0];

        $this->assertSame(['gain' => 0, 'loss' => 0], GpxTrack::gainLoss($deret, 5));
        $this->assertSame(['gain' => 8, 'loss' => 4], GpxTrack::gainLoss($deret, 1));
    }

    public function test_a_file_without_elevation_yields_no_figures_rather_than_zero(): void
    {
        $jejak = GpxTrack::trackFromXml($this->gpx($this->jejakDengan([null, null, null])));

        $this->assertNull(
            GpxTrack::gainLoss($jejak['elevations']),
            'Tanpa ketinggian, jawabannya bukan nol melainkan tidak diketahui.'
        );
    }

    /**
     * Satu titik rusak di tengah jejak tidak boleh membuang garis jalur yang selebihnya
     * baik, jadi ketinggian yang tidak masuk akal diperlakukan sebagai tidak ada.
     */
    public function test_an_impossible_elevation_is_ignored_without_losing_the_track(): void
    {
        $jejak = GpxTrack::trackFromXml($this->gpx($this->jejakDengan([1200.0, 99999.0, 1300.0])));

        $this->assertCount(3, $jejak['coordinates']);
        $this->assertSame([1200.0, null, 1300.0], $jejak['elevations']);
        $this->assertSame(['gain' => 100, 'loss' => 0], GpxTrack::gainLoss($jejak['elevations']));
    }

    public function test_the_profile_pairs_distance_with_height(): void
    {
        $jejak = GpxTrack::trackFromXml($this->gpx($this->jejakDengan([1200.0, 1250.0, 1300.0])));
        $profil = GpxTrack::profile($jejak['coordinates'], $jejak['elevations']);

        $this->assertCount(3, $profil);
        $this->assertSame(0.0, $profil[0]['km']);
        $this->assertSame(1200, $profil[0]['m']);
        $this->assertGreaterThan($profil[0]['km'], $profil[2]['km'], 'Jarak tempuh harus menaik.');
    }

    /**
     * Profil diencerkan, dan itu sah justru karena gunanya berbeda dari garis jalur:
     * yang dibaca bentuk tanjakannya, bukan tikungannya.
     */
    public function test_a_dense_track_is_thinned_but_keeps_both_ends(): void
    {
        $ketinggian = [];

        foreach (range(0, 499) as $i) {
            $ketinggian[] = 1000.0 + $i;
        }

        $jejak = GpxTrack::trackFromXml($this->gpx($this->jejakDengan($ketinggian)));
        $profil = GpxTrack::profile($jejak['coordinates'], $jejak['elevations'], 120);

        $this->assertCount(120, $profil);
        $this->assertSame(1000, $profil[0]['m'], 'Ketinggian awal tidak boleh hilang.');
        $this->assertSame(1499, $profil[119]['m'], 'Puncaknya tidak boleh terpotong.');
    }
}
