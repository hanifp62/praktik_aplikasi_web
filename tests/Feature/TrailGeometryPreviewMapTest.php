<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pratinjau impor geometri menggambar garisnya sebelum disimpan.
 *
 * Kurator sebelumnya hanya membaca jumlah titik dan panjang jejak, lalu diminta menyimpan
 * garis yang bentuk dan letaknya belum pernah ia lihat. Garis draf kini dikirim sebagai
 * pratinjau.garis dan digambar PetaJalur, dengan keterangan bahwa garis itu belum
 * tersimpan.
 */
class TrailGeometryPreviewMapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bujur dulu, seperti GeoJSON. Bila urutannya tertukar, lintangnya menjadi 112,95 yang
     * mustahil, dan pratinjau tidak lagi menunjukkan letak jalur yang sebenarnya.
     */
    public function test_the_preview_sends_the_draft_line_in_geojson_order(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $trail = Trail::factory()->create();
        $this->actingAs($admin)
            ->from(route('trails.geometry', $trail))
            ->post(route('trails.geometry.preview', $trail), ['berkas' => $this->gpx()]);

        $garis = $this->actingAs($admin)->get(route('trails.geometry', $trail))->viewData('page')['props']['pratinjau']['garis'];

        $this->assertSame('LineString', $garis['type']);
        $this->assertSame([[112.95, -7.997], [112.9505, -7.995], [112.951, -7.993]], $garis['coordinates']);
    }

    public function test_the_page_draws_the_line_as_an_unsaved_preview(): void
    {
        $halaman = File::get(resource_path('js/pages/Admin/TrailGeometry.tsx'));

        $this->assertStringContainsString('<PetaJalur', $halaman);
        $this->assertStringContainsString('pratinjau.garis', $halaman);
        $this->assertStringContainsString('belum tersimpan', $halaman);
    }

    private function gpx(): UploadedFile
    {
        $xml = '<?xml version="1.0"?><gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">'
            .'<trk><trkseg>'
            .'<trkpt lat="-7.9970" lon="112.9500"/>'
            .'<trkpt lat="-7.9950" lon="112.9505"/>'
            .'<trkpt lat="-7.9930" lon="112.9510"/>'
            .'</trkseg></trk></gpx>';

        return UploadedFile::fake()->createWithContent('jalur.gpx', $xml);
    }
}
