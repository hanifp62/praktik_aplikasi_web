<?php

namespace Tests\Unit;

use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\DataFreshnessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PRD §98 menyebut pemeriksaan data basi sebagai pekerjaan background, dan §93 menuntut
 * kesegaran data terlihat di antarmuka. Keduanya belum ada.
 *
 * Akibatnya status resmi yang kedaluwarsa diam-diam kembali menjadi UNKNOWN. Itu aman
 * menurut §95, tetapi senyap: tidak ada yang memberi tahu admin bahwa jalur yang kemarin
 * berstatus BUKA kini tidak punya status sama sekali.
 */
class DataFreshnessServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataFreshnessService $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layanan = app(DataFreshnessService::class);
        config(['hiking.freshness.status_review_days' => 90]);
        config(['hiking.freshness.status_expiry_warning_days' => 14]);
    }

    public function test_an_expired_status_needs_review(): void
    {
        $status = $this->buatStatus(['expires_at' => now()->subDay(), 'verified_at' => now()]);

        $this->assertTrue($this->layanan->expired()->contains('id', $status->id));
    }

    public function test_a_status_that_is_still_valid_is_not_expired(): void
    {
        $status = $this->buatStatus(['expires_at' => now()->addMonth(), 'verified_at' => now()]);

        $this->assertFalse($this->layanan->expired()->contains('id', $status->id));
    }

    public function test_a_status_without_an_end_date_never_expires(): void
    {
        $status = $this->buatStatus(['expires_at' => null, 'verified_at' => now()]);

        $this->assertFalse($this->layanan->expired()->contains('id', $status->id));
    }

    public function test_a_status_expiring_within_the_warning_window_is_flagged(): void
    {
        $status = $this->buatStatus(['expires_at' => now()->addDays(3), 'verified_at' => now()]);

        $this->assertTrue($this->layanan->expiringSoon()->contains('id', $status->id));
    }

    public function test_a_status_expiring_beyond_the_window_is_not_flagged_yet(): void
    {
        $status = $this->buatStatus(['expires_at' => now()->addDays(60), 'verified_at' => now()]);

        $this->assertFalse($this->layanan->expiringSoon()->contains('id', $status->id));
    }

    /**
     * Status yang sudah kedaluwarsa tidak perlu diperingatkan sebagai "akan kedaluwarsa".
     * Menampilkannya di dua daftar sekaligus membuat admin menghitung ganda.
     */
    public function test_an_already_expired_status_is_not_also_counted_as_expiring_soon(): void
    {
        $status = $this->buatStatus(['expires_at' => now()->subDay(), 'verified_at' => now()]);

        $this->assertFalse($this->layanan->expiringSoon()->contains('id', $status->id));
    }

    public function test_a_status_not_verified_for_too_long_needs_review(): void
    {
        $status = $this->buatStatus(['verified_at' => now()->subDays(120), 'expires_at' => null]);

        $this->assertTrue($this->layanan->stale()->contains('id', $status->id));
    }

    public function test_a_recently_verified_status_is_not_stale(): void
    {
        $status = $this->buatStatus(['verified_at' => now()->subDays(10), 'expires_at' => null]);

        $this->assertFalse($this->layanan->stale()->contains('id', $status->id));
    }

    /**
     * Status yang tidak pernah diverifikasi sama sekali adalah kasus yang paling perlu
     * ditinjau, bukan yang paling aman diabaikan.
     */
    public function test_a_status_never_verified_needs_review(): void
    {
        $status = $this->buatStatus(['verified_at' => null, 'expires_at' => null]);

        $this->assertTrue($this->layanan->stale()->contains('id', $status->id));
    }

    public function test_the_review_count_adds_up_the_three_categories(): void
    {
        $this->buatStatus(['expires_at' => now()->subDay(), 'verified_at' => now()]);
        $this->buatStatus(['expires_at' => now()->addDays(3), 'verified_at' => now()]);
        $this->buatStatus(['expires_at' => null, 'verified_at' => now()->subDays(200)]);
        $this->buatStatus(['expires_at' => null, 'verified_at' => now()]);

        $this->assertSame(3, $this->layanan->reviewCount());
    }

    public function test_the_review_count_costs_one_query(): void
    {
        $this->buatStatus(['expires_at' => now()->subDay(), 'verified_at' => now()]);

        $jumlah = 0;
        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $this->layanan->reviewCount();

        $this->assertSame(1, $jumlah, 'Hitungan ini tampil di setiap halaman admin.');
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatStatus(array $atribut): OfficialStatus
    {
        return OfficialStatus::factory()->create(array_merge([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create()->id,
            'effective_at' => now()->subMonth(),
        ], $atribut));
    }
}
