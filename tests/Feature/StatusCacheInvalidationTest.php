<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\UserRole;
use App\Livewire\Admin\OfficialStatusManager;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Snapshot status resmi dilayani dari cache selama 300 detik, dan pembersihnya dipasang
 * sebagai observer saved/deleted pada model OfficialStatus.
 *
 * Perilaku itu benar hari ini. Yang belum ada adalah yang menahannya tetap begitu:
 * observer adalah sambungan yang tidak terlihat dari sisi pemanggil mana pun, jadi ia
 * dapat terhapus dalam perapian kode tanpa satu pun test lain berubah warna. Kedua test
 * di bawah sudah diperiksa gagal ketika observer-nya dilepas.
 *
 * Yang dijaga bukan detail cache, melainkan janji yang menyertainya: penutupan jalur
 * terlihat seketika, dan lima menit menjawab "BUKA" setelah balai menutup jalur adalah
 * lima menit yang salah pada pertanyaan paling penting di aplikasi ini.
 */
class StatusCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    private function statusTercache(Trail $trail): string
    {
        return app(OfficialStatusService::class)->cachedSnapshotForTrail($trail)['status'];
    }

    public function test_closing_a_trail_is_visible_before_the_cache_expires(): void
    {
        $trail = Trail::factory()->easy()->create();

        // Menghangatkan cache lebih dulu, persis seperti pendaki yang membuka halaman
        // jalur sesaat sebelum balai mencatat penutupan.
        $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail));

        Livewire::actingAs($this->admin())
            ->test(OfficialStatusManager::class)
            ->set('scope', 'TRAIL')
            ->set('statusable_id', $trail->id)
            ->set('status', OfficialStatusValue::CLOSED->value)
            ->set('reason', 'Kebakaran lahan di jalur pendakian.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            OfficialStatusValue::CLOSED->value,
            $this->statusTercache($trail->fresh()),
            'Penutupan harus terlihat seketika, bukan setelah TTL cache habis.'
        );
    }

    /**
     * Cakupannya mengikuti cascade §42: menutup gunung menutup seluruh jalurnya, jadi
     * cache seluruh jalur itu yang harus dibuang, bukan satu saja. Ini bagian yang
     * paling mudah hilang diam-diam, karena versi yang hanya membersihkan satu jalur
     * tetap terlihat benar pada gunung berjalur tunggal.
     */
    public function test_closing_a_mountain_clears_every_trail_beneath_it(): void
    {
        $mountain = Mountain::factory()->create();
        $trails = Trail::factory()->count(3)->easy()->for($mountain)->create();

        foreach ($trails as $trail) {
            $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail));
        }

        Livewire::actingAs($this->admin())
            ->test(OfficialStatusManager::class)
            ->set('scope', 'MOUNTAIN')
            ->set('statusable_id', $mountain->id)
            ->set('status', OfficialStatusValue::CLOSED->value)
            ->set('reason', 'Aktivitas vulkanik meningkat.')
            ->call('save')
            ->assertHasNoErrors();

        foreach ($trails as $trail) {
            $this->assertSame(
                OfficialStatusValue::CLOSED->value,
                $this->statusTercache($trail->fresh()),
                "Jalur {$trail->id} masih menjawab dengan status lama setelah gunungnya ditutup."
            );
        }
    }
}
