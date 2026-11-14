<?php

namespace Tests\Feature;

use App\Models\ComparisonShare;
use App\Services\SchedulerHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Baris tautan bagikan yang sudah berakhir (titipan W3 M8 untuk W9).
 *
 * Tautan yang dicabut atau kedaluwarsa tidak lagi membuka apa pun, tetapi barisnya tetap
 * mengaitkan satu akun dengan pilihan jalurnya. docs/KEBIJAKAN-DATA-PRIBADI.md bagian 3 menyebut
 * data tanpa dasar retensi terdokumentasi diperlakukan sebagai dihapus, tetapi lama simpannya
 * keputusan pemilik di berkas itu. Karena itu pemangkasnya dibangun di balik
 * SHARE_LINK_PRUNE_AFTER_DAYS yang kosong secara bawaan: tugas hariannya berjalan dan dipantau,
 * tetapi tidak menghapus apa pun sampai pemilik mengisi angkanya.
 */
class ShareLinkRetentionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{aktif: ComparisonShare, baruBerakhir: ComparisonShare, lamaKedaluwarsa: ComparisonShare, lamaDicabut: ComparisonShare}
     */
    private function tautan(): array
    {
        return [
            'aktif' => ComparisonShare::factory()->create(['expires_at' => now()->addDays(3)]),
            'baruBerakhir' => ComparisonShare::factory()->create(['expires_at' => now()->subDays(5)]),
            'lamaKedaluwarsa' => ComparisonShare::factory()->create(['expires_at' => now()->subDays(40)]),
            'lamaDicabut' => ComparisonShare::factory()->create(['expires_at' => now()->addDays(2), 'revoked_at' => now()->subDays(31)]),
        ];
    }

    public function test_nothing_is_pruned_until_the_owner_sets_a_retention(): void
    {
        $this->assertEmpty(config('hiking.share_links.prune_after_days'));
        $this->tautan();

        $this->artisan('model:prune', ['--model' => [ComparisonShare::class]])->assertSuccessful();

        $this->assertSame(4, ComparisonShare::query()->count());
    }

    public function test_links_that_ended_before_the_retention_are_pruned_and_the_rest_stay(): void
    {
        config(['hiking.share_links.prune_after_days' => 30]);
        $tautan = $this->tautan();

        $this->artisan('model:prune', ['--model' => [ComparisonShare::class]])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [$tautan['aktif']->id, $tautan['baruBerakhir']->id],
            ComparisonShare::query()->pluck('id')->all()
        );
    }

    public function test_the_pruning_runs_daily_and_is_monitored(): void
    {
        $tugas = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'model:prune') && str_contains((string) $event->command, 'ComparisonShare'));

        $this->assertNotNull($tugas, 'Pemangkasan tautan bagikan tidak terjadwal.');
        $this->assertSame('0 20 * * *', $tugas->expression);
        $this->assertArrayHasKey('model:prune', SchedulerHealthService::TUGAS);
    }
}
