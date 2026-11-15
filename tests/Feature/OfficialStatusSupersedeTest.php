<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Services\DataFreshnessService;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Rantai penggantian status resmi (spec v3 bagian 10.7; rencana induk W8 butir 3; ruling W8-R11).
 *
 * Saat sebuah catatan mulai terlihat pendaki, catatan lain untuk sasaran yang sama yang sudah
 * mulai berlaku sebelum tanggal mulainya ditandai digantikan dan berakhir pada tanggal itu.
 * Catatan yang baru berlaku sesudahnya (pengumuman ke depan) tidak disentuh. Catatan yang
 * digantikan tidak pernah hidup kembali: sesudah catatan baru berakhir, statusnya Belum
 * diketahui sampai ada catatan berikutnya (R-006, R-007). Tanggal berakhir lama tetap terbaca di
 * jejak audit.
 */
class OfficialStatusSupersedeTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    private function nilai(): OfficialStatusValue
    {
        return app(OfficialStatusService::class)->currentValueFor($this->gunung);
    }

    public function test_publishing_supersedes_older_records_from_its_start(): void
    {
        $lama = $this->catatanLama(OfficialStatusValue::OPEN);
        $kurator = $this->kurator();

        $baru = app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED));

        $this->assertSame($baru->id, $lama->fresh()->superseded_by_id);
        $this->assertTrue($lama->fresh()->expires_at->equalTo($baru->effective_at));
        $this->assertSame($lama->id, $baru->replaces->id);
        $this->assertSame($baru->id, $lama->fresh()->supersededBy->id);
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilai());
    }

    /**
     * Penutupan yang diumumkan untuk tanggal yang belum tiba tidak memotong status yang berlaku
     * hari ini lebih awal; sesudah penutupan itu berakhir, status lama tidak hidup kembali.
     */
    public function test_a_future_record_keeps_the_current_one_until_it_starts_and_never_revives_it(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN);
        $kurator = $this->kurator();
        app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED, [
            'effective_at' => now()->addDays(3)->startOfDay(),
            'end_condition' => StatusEndCondition::ON_DATE->value,
            'expires_at' => now()->addDays(10)->endOfDay(),
        ]));

        $this->assertSame(OfficialStatusValue::OPEN, $this->nilai());

        $this->travel(4)->days();
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilai());

        $this->travel(10)->days();
        $this->assertSame(OfficialStatusValue::UNKNOWN, $this->nilai(), 'Status lama tidak hidup kembali sesudah penutupan berakhir.');
    }

    public function test_records_that_start_later_are_left_alone(): void
    {
        $nanti = $this->catatanLama(OfficialStatusValue::CLOSED, ['effective_at' => now()->addDays(20)]);
        $kurator = $this->kurator();

        app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::RESTRICTED));

        $this->assertNull($nanti->fresh()->superseded_by_id);
        $this->assertNull($nanti->fresh()->expires_at);
    }

    public function test_a_superseded_record_leaves_the_review_queue(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN, ['verified_at' => now()->subYear()]);
        $kurator = $this->kurator();

        app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED));

        $tinjau = app(DataFreshnessService::class)->review();
        $this->assertCount(0, $tinjau['kedaluwarsa'], 'Catatan yang digantikan bukan catatan kedaluwarsa.');
        $this->assertCount(0, $tinjau['basi']->where('status', OfficialStatusValue::OPEN));
    }
}
