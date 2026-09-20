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
 * Foto yang diunggah pendaki selama ini hanya tampil di antrean moderasi. Aplikasi
 * mengumpulkan gambar gunung lalu menyembunyikannya dari orang yang paling
 * membutuhkannya, padahal foto kondisi terkini adalah satu-satunya hal yang dapat
 * menunjukkan bahwa jembatan di Pos 3 putus atau sabananya sedang terbakar.
 *
 * Aturan aksesnya sudah benar sejak awal: policy mengizinkan siapa pun melihat foto
 * laporan yang lolos moderasi. Yang belum ada hanya tempat menampilkannya.
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

    private function bukaJalur(Trail $trail)
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.show', $trail));
    }

    public function test_an_approved_photo_is_shown_to_hikers(): void
    {
        $trail = Trail::factory()->easy()->create();
        $laporan = $this->laporan($trail, ModerationStatus::APPROVED);

        $this->bukaJalur($trail)
            ->assertOk()
            ->assertSee(route('reports.photo', $laporan), escape: false);
    }

    /**
     * Foto yang belum dimoderasi tidak boleh bocor lewat halaman jalur. Kalau bocor,
     * seluruh gunanya moderasi hilang justru di pintu yang paling ramai.
     */
    public function test_a_photo_awaiting_moderation_never_appears(): void
    {
        $trail = Trail::factory()->easy()->create();
        $laporan = $this->laporan($trail, ModerationStatus::PENDING);

        $this->bukaJalur($trail)
            ->assertOk()
            ->assertDontSee(route('reports.photo', $laporan), escape: false);
    }

    public function test_a_rejected_photo_never_appears(): void
    {
        $trail = Trail::factory()->easy()->create();
        $laporan = $this->laporan($trail, ModerationStatus::REJECTED);

        $this->bukaJalur($trail)
            ->assertOk()
            ->assertDontSee(route('reports.photo', $laporan), escape: false);
    }

    public function test_a_photo_from_another_trail_never_appears(): void
    {
        $trail = Trail::factory()->easy()->create();
        $lain = $this->laporan(Trail::factory()->easy()->create(), ModerationStatus::APPROVED);

        $this->bukaJalur($trail)
            ->assertOk()
            ->assertDontSee(route('reports.photo', $lain), escape: false);
    }

    /**
     * Foto tanpa keterangan siapa dan kapan hanyalah gambar gunung. Yang membuatnya
     * berguna justru tanggalnya: foto sabana hijau dari delapan bulan lalu menyesatkan
     * pendaki yang berangkat musim kemarau.
     */
    public function test_each_photo_carries_who_took_it_and_when(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->laporan($trail, ModerationStatus::APPROVED);

        $halaman = $this->bukaJalur($trail);

        $halaman->assertSee('Rina Pendaki');
        $halaman->assertSee(now()->subDays(3)->translatedFormat('d M Y'));
    }

    /**
     * PRD §92: masukan komunitas tidak boleh terbaca sebagai keterangan resmi. Foto
     * adalah bentuk masukan yang paling meyakinkan sekaligus paling mudah disalahartikan
     * sebagai keterangan pengelola, jadi penandanya harus ikut.
     */
    public function test_the_photos_are_marked_as_community_input_not_official(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->laporan($trail, ModerationStatus::APPROVED);

        $this->bukaJalur($trail)->assertSee('community-', escape: false);
    }

    /**
     * Laporan tanpa foto tidak boleh menghasilkan bingkai gambar rusak.
     */
    public function test_a_report_without_a_photo_leaves_no_broken_frame(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->laporan($trail, ModerationStatus::APPROVED, foto: null);

        $this->bukaJalur($trail)
            ->assertOk()
            ->assertDontSee('<img', escape: false);
    }
}
