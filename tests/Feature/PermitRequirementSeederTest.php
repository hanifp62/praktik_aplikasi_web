<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\PermitRequirement;
use Database\Seeders\MountainSeeder;
use Database\Seeders\PermitRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan perizinan adalah catatan tentang aturan pihak lain. Yang paling berbahaya di
 * sini bukan data yang kosong, melainkan data yang diisi supaya terlihat lengkap:
 * pendaki merencanakan tanggal keberangkatan berdasarkan angka ini.
 */
class PermitRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MountainSeeder::class);
        $this->seed(PermitRequirementSeeder::class);
    }

    public function test_semeru_records_the_published_quota_and_booking_window(): void
    {
        $izin = $this->izin('gunung-semeru');

        $this->assertSame(200, $izin->daily_quota);
        $this->assertSame(30, $izin->booking_opens_days_before);
        $this->assertSame(2, $izin->booking_closes_days_before);
        $this->assertTrue($izin->guide_required);
        $this->assertSame(2, $izin->max_duration_days);
    }

    /**
     * Pendaki yang merencanakan Mahameru sedang merencanakan sesuatu yang dilarang.
     * Batas itu harus terbaca, bukan tersirat.
     */
    public function test_the_semeru_summit_ban_is_written_down(): void
    {
        $catatan = $this->izin('gunung-semeru')->notes;

        $this->assertStringContainsString('Ranu Kumbolo', $catatan);
        $this->assertStringContainsString('Mahameru', $catatan);
    }

    /**
     * Kuota Rinjani tidak diumumkan. Mengisinya dengan angka yang terdengar masuk akal
     * akan membuat pendaki merencanakan berdasarkan karangan.
     */
    public function test_rinjani_leaves_unpublished_numbers_empty(): void
    {
        $izin = $this->izin('gunung-rinjani');

        $this->assertNull($izin->daily_quota);
        $this->assertNull($izin->booking_opens_days_before);
        $this->assertNull($izin->booking_closes_days_before);
    }

    /**
     * Fakta paling menentukan bagi pendaki Rinjani hari ini: pintu pemesanannya tertutup.
     */
    public function test_the_suspended_booking_service_is_stated_plainly(): void
    {
        $catatan = $this->izin('gunung-rinjani')->notes;

        $this->assertStringContainsString('eRinjani', $catatan);
        $this->assertStringContainsString('DIHENTIKAN SEMENTARA', $catatan);
    }

    public function test_every_rule_carries_its_source_and_verification_date(): void
    {
        foreach (PermitRequirement::whereNotNull('mountain_id')->get() as $izin) {
            if ($izin->source === null) {
                continue; // aturan lama dari dataset contoh
            }

            $this->assertNotNull($izin->source_url, 'Aturan bersumber tanpa tautannya.');
            $this->assertNotNull($izin->verified_at);
        }
    }

    /**
     * §92: dicatat dari pemberitaan atas pengumuman, bukan dari kanal balai langsung.
     */
    public function test_the_source_does_not_overstate_its_authority(): void
    {
        $this->assertSame('ADMIN_VERIFIED', $this->izin('gunung-semeru')->dataSource->source_type->value);
    }

    public function test_running_the_seeder_twice_does_not_duplicate(): void
    {
        $sebelum = PermitRequirement::count();

        $this->seed(PermitRequirementSeeder::class);

        $this->assertSame($sebelum, PermitRequirement::count());
    }

    private function izin(string $slug): PermitRequirement
    {
        $gunung = Mountain::where('slug', $slug)->firstOrFail();

        return PermitRequirement::where('mountain_id', $gunung->id)->firstOrFail();
    }
}
