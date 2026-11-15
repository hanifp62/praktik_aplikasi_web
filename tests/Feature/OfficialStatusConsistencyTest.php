<?php

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Models\Trail;
use App\Services\DataResmi\PemeriksaanKonsistensi;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Pemeriksaan konsistensi otomatis sebelum terbit (spec v3 bagian 10.7; rencana induk W8 butir 4;
 * ruling W8-R12; Q12 sebagai peringatan saja).
 *
 * Temuan "blokir" menahan kiriman dan verifikasi di lapisan layanan: tanggal terbalik, cara
 * berakhir yang tidak cocok dengan tanggalnya, sasaran di luar kawasan badan penerbit, tanpa arsip
 * bukti, dan pembukaan yang hanya berbukti berita. Temuan "peringatan" tampil di meja kurator dan
 * tidak menahan apa pun: dua catatan berlaku yang bertentangan, bukti hanya berita, dan jalur Buka
 * di kawasan yang ditutup. Peringatan kawasan tidak mengubah status jalur maupun rekomendasi
 * pendaki sampai pemilik memutus Q12.
 */
class OfficialStatusConsistencyTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    /**
     * @param  array<int, array{kode: string, tingkat: string, pesan: string}>  $temuan
     * @return array<int, string>
     */
    private function kode(array $temuan, string $tingkat): array
    {
        return array_values(array_map(fn (array $t) => $t['kode'], array_filter($temuan, fn (array $t) => $t['tingkat'] === $tingkat)));
    }

    public function test_a_consistent_record_has_no_findings(): void
    {
        $draf = $this->draf($this->kurator(), OfficialStatusValue::CLOSED);

        $this->assertSame([], app(PemeriksaanKonsistensi::class)->periksa($draf));
    }

    public function test_reversed_dates_block_submission(): void
    {
        $kurator = $this->kurator();
        $draf = $this->draf($kurator, OfficialStatusValue::CLOSED, [
            'effective_at' => now()->addDays(5),
            'end_condition' => StatusEndCondition::ON_DATE->value,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame(['tanggal_terbalik'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf), 'blokir'));

        $this->expectException(ValidationException::class);
        app(TerbitStatus::class)->kirim($kurator, $draf);
    }

    public function test_an_end_condition_must_match_its_date(): void
    {
        $draf = $this->draf($this->kurator(), OfficialStatusValue::CLOSED, ['end_condition' => StatusEndCondition::ON_DATE->value, 'expires_at' => null]);
        $sampaiPemberitahuan = $this->draf($this->kurator(), OfficialStatusValue::CLOSED, ['end_condition' => StatusEndCondition::UNTIL_NOTICE->value, 'expires_at' => now()->addWeek()]);

        $this->assertSame(['akhir_tanpa_tanggal'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf), 'blokir'));
        $this->assertSame(['tanggal_tanpa_akhir'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($sampaiPemberitahuan), 'blokir'));
    }

    /**
     * Kawasan badan dapat berubah sesudah draf dibuat. Pemeriksaan membaca kawasan saat itu,
     * bukan saat draf ditulis.
     */
    public function test_a_target_outside_the_authority_area_blocks_submission(): void
    {
        $kurator = $this->kurator();
        $draf = $this->draf($kurator, OfficialStatusValue::CLOSED);
        $this->balai->mountains()->detach($this->gunung);

        $this->assertContains('di_luar_yurisdiksi', $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf->fresh()), 'blokir'));
    }

    public function test_a_missing_archive_blocks_submission(): void
    {
        $draf = app(TerbitStatus::class)->buatDraf($this->kurator(), $this->gunung, $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED), null);

        $this->assertSame(['tanpa_arsip'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf), 'blokir'));
    }

    public function test_news_evidence_warns_and_never_opens(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $tutup = $this->draf($this->kurator(), OfficialStatusValue::CLOSED, ['evidence_type' => EvidenceType::NEWS->value]);
        $buka = $this->draf($this->kurator(), OfficialStatusValue::OPEN, ['evidence_type' => EvidenceType::NEWS->value]);

        $this->assertSame(['bukti_berita'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($tutup), 'peringatan'));
        $this->assertSame([], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($tutup), 'blokir'));
        $this->assertSame(['berita_membuka'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($buka), 'blokir'));
    }

    public function test_conflicting_records_for_the_same_target_warn(): void
    {
        $kuratorLain = $this->kurator();
        app(TerbitStatus::class)->kirim($kuratorLain, $this->draf($kuratorLain, OfficialStatusValue::OPEN));
        $nanti = $this->catatanLama(OfficialStatusValue::RESTRICTED, ['effective_at' => now()->addDays(5)]);
        $draf = $this->draf($this->kurator(), OfficialStatusValue::CLOSED);

        $temuan = app(PemeriksaanKonsistensi::class)->periksa($draf);

        $this->assertSame(['bertentangan', 'bertentangan'], $this->kode($temuan, 'peringatan'));
        $this->assertStringContainsString('menunggu verifikasi kedua', $temuan[0]['pesan'].$temuan[1]['pesan']);
        $this->assertStringContainsString('#'.$nanti->id, $temuan[0]['pesan'].$temuan[1]['pesan']);
    }

    /**
     * Q12 belum diputus: jalur Buka di gunung yang ditutup hanya diperingatkan. Status jalur tetap
     * mengikuti kaskade §42 yang sudah ada, dan kiriman tidak ditahan.
     */
    public function test_an_open_trail_inside_a_closed_area_only_warns(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $jalur = Trail::factory()->for($this->gunung)->create();
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::OPEN, [], $jalur);

        $temuan = app(PemeriksaanKonsistensi::class)->periksa($draf);

        $this->assertSame(['kawasan_tertutup'], $this->kode($temuan, 'peringatan'));
        $this->assertSame([], $this->kode($temuan, 'blokir'));

        $menunggu = app(TerbitStatus::class)->kirim($pencatat, $draf);
        app(TerbitStatus::class)->verifikasi($this->kurator(), $menunggu);

        $this->assertSame(OfficialStatusValue::CLOSED, app(OfficialStatusService::class)->effectiveStatusForTrail($jalur->fresh()), 'Kaskade §42 tidak berubah.');
    }
}
