<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Foto komunitas dikembalikan kepada pendaki.
 *
 * Foto kondisi terkini adalah satu-satunya hal yang dapat menunjukkan bahwa jembatan di
 * Pos 3 putus atau sabananya sedang terbakar. Aturan aksesnya sudah benar sejak awal:
 * policy mengizinkan siapa pun melihat foto laporan yang lolos moderasi. Yang dijaga di
 * sini: hanya foto seperti itu yang sampai ke props halaman jalur.
 */
class CommunityPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function laporan(Trail $trail, ModerationStatus $status, ?string $foto = 'laporan/uji.jpg'): TrailConditionReport
    {
        return TrailConditionReport::factory()->for($trail)->create([
            'user_id' => User::factory()->create(['name' => 'Rina Pendaki'])->id,
            'moderation_status' => $status->value,
            'photo_path' => $foto,
            'hike_date' => now()->subDays(3)->toDateString(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function laporanDiHalaman(Trail $trail): array
    {
        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->viewData('page')['props']['komunitas']['reports'];
    }

    /**
     * @return array<int, string|null>
     */
    private function foto(Trail $trail): array
    {
        return array_column($this->laporanDiHalaman($trail), 'photoUrl');
    }

    public function test_an_approved_photo_is_shown_to_hikers(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $laporan = $this->laporan($trail, ModerationStatus::APPROVED);

        $this->assertSame([route('reports.photo', $laporan)], $this->foto($trail));
    }

    /**
     * Foto yang belum dimoderasi tidak boleh bocor lewat halaman jalur. Kalau bocor,
     * seluruh gunanya moderasi hilang justru di pintu yang paling ramai.
     */
    public function test_a_photo_awaiting_moderation_never_appears(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $laporan = $this->laporan($trail, ModerationStatus::PENDING);

        $this->assertNotContains(route('reports.photo', $laporan), $this->foto($trail));
        $this->assertStringNotContainsString('laporan/uji.jpg', json_encode($this->laporanDiHalaman($trail)));
    }

    public function test_a_rejected_photo_never_appears(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $laporan = $this->laporan($trail, ModerationStatus::REJECTED);

        $this->assertNotContains(route('reports.photo', $laporan), $this->foto($trail));
    }

    public function test_a_photo_from_another_trail_never_appears(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $lain = $this->laporan(Trail::factory()->published()->easy()->create(), ModerationStatus::APPROVED);

        $this->assertNotContains(route('reports.photo', $lain), $this->foto($trail));
    }

    /**
     * Foto tanpa keterangan siapa dan kapan hanyalah gambar gunung: foto sabana hijau dari
     * delapan bulan lalu menyesatkan pendaki yang berangkat musim kemarau.
     */
    public function test_each_photo_carries_who_took_it_and_when(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->laporan($trail, ModerationStatus::APPROVED);

        $laporan = $this->laporanDiHalaman($trail)[0];

        $this->assertSame('Rina Pendaki', $laporan['pelapor']);
        $this->assertSame(now()->subDays(3)->translatedFormat('d M Y'), $laporan['tanggalPendakian']);
        $this->assertStringContainsString($laporan['tanggalPendakian'], $laporan['photoAlt']);
    }

    /**
     * PRD §92: masukan komunitas tidak boleh terbaca sebagai keterangan resmi. Foto adalah
     * bentuk masukan yang paling meyakinkan, jadi label "Komunitas" ikut di kartunya, dan
     * foto tetap di dalam laporannya, bukan gambar utama halaman (R-017).
     */
    public function test_the_photos_are_marked_as_community_input_not_official(): void
    {
        $kartu = file_get_contents(resource_path('js/components/trails/rinci/LaporanKomunitas.tsx'));

        $this->assertStringContainsString('<LabelKomunitas />', $kartu);
        $this->assertStringContainsString('Laporan kondisi dari komunitas', $kartu);
        // Sejak W3 label sumber tinggal di components/trails/LabelSumber.tsx dan memakai
        // Lencana bernada komunitas bergaris putus-putus (spec v3 bagian 7).
        $this->assertStringContainsString("COMMUNITY: { label: 'Komunitas', ikon: 'user', nada: 'komunitas', garis: 'putus' }", file_get_contents(resource_path('js/components/trails/LabelSumber.tsx')));
        $this->assertStringContainsString("komunitas: { isi: 'bg-community-50 text-community-900'", file_get_contents(resource_path('js/components/ui/Lencana.tsx')));
        $this->assertStringNotContainsString('<img', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Laporan tanpa foto tidak boleh menghasilkan bingkai gambar rusak.
     */
    public function test_a_report_without_a_photo_leaves_no_broken_frame(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->laporan($trail, ModerationStatus::APPROVED, foto: null);

        $this->assertSame([null], $this->foto($trail));
        $this->assertStringContainsString('{r.photoUrl ? (', file_get_contents(resource_path('js/components/trails/rinci/LaporanKomunitas.tsx')));
    }
}
