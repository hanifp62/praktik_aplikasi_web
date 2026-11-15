<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\UserRole;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
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
    use MejaKurator;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    private function statusTercache(Trail $trail): string
    {
        return app(OfficialStatusService::class)->cachedSnapshotForTrail($trail)['status'];
    }

    /**
     * Penutupan dicatat lewat meja kurator sejak W8 (ruling W8-R16): satu kurator dengan bukti
     * dari pengelola, terbit seketika. Niatnya sama dengan sebelum W8.
     */
    private function tutupLewatMeja(string $sasaran, string $alasan): void
    {
        $kurator = $this->kurator();

        $this->actingAs($kurator)
            ->post(route('curator.statuses.store'), [
                'sasaran' => $sasaran,
                'otoritas' => $this->balai->id,
                'status' => OfficialStatusValue::CLOSED->value,
                'jenis_bukti' => 'OFFICIAL_LETTER',
                'bukti' => $this->suratPdf(),
                'mulai_berlaku' => now()->subDay()->toDateString(),
                'cara_berakhir' => 'UNTIL_NOTICE',
                'alasan' => $alasan,
                'ringkasan' => $alasan,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($kurator)->post(route('curator.statuses.submit', OfficialStatus::latest('id')->firstOrFail()))->assertSessionHasNoErrors();
    }

    public function test_closing_a_trail_is_visible_before_the_cache_expires(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $trail = Trail::factory()->easy()->for($this->gunung)->create();

        // Menghangatkan cache lebih dulu, persis seperti pendaki yang membuka halaman
        // jalur sesaat sebelum balai mencatat penutupan.
        $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail));

        $this->tutupLewatMeja('TRAIL:'.$trail->id, 'Kebakaran lahan di jalur pendakian.');

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
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $trails = Trail::factory()->count(3)->easy()->for($this->gunung)->create();

        foreach ($trails as $trail) {
            $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail));
        }

        $this->tutupLewatMeja('MOUNTAIN:'.$this->gunung->id, 'Aktivitas vulkanik meningkat.');

        foreach ($trails as $trail) {
            $this->assertSame(
                OfficialStatusValue::CLOSED->value,
                $this->statusTercache($trail->fresh()),
                "Jalur {$trail->id} masih menjawab dengan status lama setelah gunungnya ditutup."
            );
        }
    }

    public function test_cached_snapshot_ttl_never_outlives_an_upcoming_scheduled_closure_or_expiry(): void
    {
        config(['hiking.cache.public_ttl_seconds' => 300]);

        $trail = Trail::factory()->easy()->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'effective_at' => now()->subHour(),
            'expires_at' => null,
        ]);

        // Penutupan terjadwal 10 detik ke depan: cache tidak boleh menahan BUKA selama 300 detik.
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'effective_at' => now()->addSeconds(10),
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertSame(OfficialStatusValue::OPEN->value, $this->statusTercache($trail));

        $this->travel(11)->seconds();

        $this->assertSame(
            OfficialStatusValue::CLOSED->value,
            $this->statusTercache($trail),
            'Penutupan terjadwal harus langsung berlaku setelah melewati effective_at.'
        );
    }

    /**
     * Status yang mulai berlaku karena waktunya tiba tidak memicu event model apa pun, jadi
     * observer di atas tidak menolongnya (titipan W4-6h OOS6). Snapshot tidak boleh disimpan
     * melewati batas waktu catatan berikutnya: "Sejak cek terakhir" di Cek kesiapan membaca
     * snapshot ini, sedangkan kartu status membaca langsung, dan keduanya harus sepakat.
     */
    public function test_a_scheduled_closure_is_visible_the_moment_it_starts(): void
    {
        $trail = Trail::factory()->easy()->create();
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'status' => OfficialStatusValue::OPEN->value,
            'effective_at' => now()->subDay(),
        ]);
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'effective_at' => now()->addMinutes(2),
        ]);

        $this->assertSame(OfficialStatusValue::OPEN->value, $this->statusTercache($trail));

        $this->travel(3)->minutes();

        $this->assertSame(OfficialStatusValue::CLOSED->value, $this->statusTercache($trail->fresh()));
        $this->assertSame(OfficialStatusValue::CLOSED, app(OfficialStatusService::class)->effectiveStatusForTrail($trail->fresh()));
    }

    public function test_an_expiring_record_leaves_the_cache_when_it_ends(): void
    {
        $trail = Trail::factory()->easy()->create();
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'effective_at' => now()->subDay(),
            'expires_at' => now()->addMinute(),
        ]);

        $this->assertSame(OfficialStatusValue::CLOSED->value, $this->statusTercache($trail));

        $this->travel(2)->minutes();

        $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail->fresh()), 'Penutupan yang berakhir menjadi Belum diketahui, tidak pernah Buka.');
    }

    /**
     * Pembukaan dari meja kurator baru terlihat sesudah verifikasi orang kedua (W8), dan saat itu
     * juga snapshot setiap jalur gunungnya dibuang: penggantian catatan lama dan verifikasi sama-sama
     * tulisan model.
     */
    public function test_a_verified_opening_is_visible_before_the_cache_expires(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $trail = Trail::factory()->easy()->for($this->gunung)->create();
        $this->catatanLama(OfficialStatusValue::CLOSED, [], $trail);
        $pencatat = $this->kurator();

        $menunggu = app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::OPEN, [], $trail));
        $this->assertSame(OfficialStatusValue::CLOSED->value, $this->statusTercache($trail));

        app(TerbitStatus::class)->verifikasi($this->kurator(), $menunggu);

        $this->assertSame(OfficialStatusValue::OPEN->value, $this->statusTercache($trail->fresh()));
    }

    /**
     * Snapshot membawa jenis sumber (Resmi, Komunitas, ...), dan jenis itu milik baris
     * data_sources, bukan baris status (titipan W3-1j). Mengubah jenis sumber harus terlihat
     * seketika di setiap jalur yang statusnya mengutip sumber itu.
     */
    public function test_changing_a_source_type_is_visible_before_the_cache_expires(): void
    {
        $sumber = DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value]);
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->easy()->for($mountain)->create();
        OfficialStatus::factory()->create([
            'statusable_type' => $mountain->getMorphClass(),
            'statusable_id' => $mountain->id,
            'scope' => 'MOUNTAIN',
            'status' => OfficialStatusValue::CLOSED->value,
            'data_source_id' => $sumber->id,
        ]);

        $this->assertSame(SourceType::OFFICIAL->value, app(OfficialStatusService::class)->cachedSnapshotForTrail($trail)['source_type']);

        $this->actingAs($this->admin())
            ->put(route('admin.sources.update', $sumber), [
                'source_name' => $sumber->source_name,
                'source_type' => SourceType::COMMUNITY->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(SourceType::COMMUNITY->value, app(OfficialStatusService::class)->cachedSnapshotForTrail($trail->fresh())['source_type']);
    }
}
