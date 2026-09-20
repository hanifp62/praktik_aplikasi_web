<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Services\OfficialStatusService;
use Database\Seeders\MountainSeeder;
use Database\Seeders\OfficialStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Status resmi yang dicatat adalah keadaan nyata September 2026, dan yang tidak dicatat
 * sama pentingnya: sepuluh gunung sengaja dibiarkan tanpa keterangan karena memang tidak
 * ditemukan keterangannya.
 *
 * Diam adalah cara sistem ini mengatakan tidak tahu. Mengisinya dengan OPEN akan membuat
 * §95 berbohong, dan pada produk keselamatan kebohongan itu berarah satu: menyuruh orang
 * berangkat ke jalur yang mungkin tertutup.
 */
class OfficialStatusSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MountainSeeder::class);
        $this->seed(OfficialStatusSeeder::class);
    }

    public function test_the_closed_parks_are_recorded_as_closed(): void
    {
        foreach (['gunung-gede', 'gunung-pangrango', 'gunung-semeru'] as $slug) {
            $this->assertSame(
                OfficialStatusValue::CLOSED,
                $this->statusTerkini($slug),
                $slug.' tercatat ditutup pada sumbernya.'
            );
        }
    }

    public function test_limited_openings_are_recorded_as_restricted_not_open(): void
    {
        foreach (['gunung-merbabu', 'gunung-rinjani'] as $slug) {
            $this->assertSame(
                OfficialStatusValue::RESTRICTED,
                $this->statusTerkini($slug),
                $slug.' dibuka terbatas, dan terbatas bukan terbuka.'
            );
        }
    }

    /**
     * Inti §95. Sepuluh gunung tanpa keterangan harus menjawab UNKNOWN, bukan OPEN.
     */
    public function test_mountains_without_a_published_notice_answer_unknown(): void
    {
        $tanpaKeterangan = [
            'gunung-prau', 'gunung-sindoro', 'gunung-sumbing', 'gunung-lawu',
            'gunung-arjuno', 'gunung-salak', 'gunung-andong', 'gunung-ungaran',
            'gunung-slamet', 'gunung-raung', 'gunung-papandayan',
        ];

        foreach ($tanpaKeterangan as $slug) {
            $this->assertSame(
                OfficialStatusValue::UNKNOWN,
                $this->statusTerkini($slug),
                $slug.' tidak punya keterangan, jadi jawabannya harus belum diketahui.'
            );
        }
    }

    /**
     * §60: angka tanpa asal tidak dapat dinilai pembacanya.
     */
    public function test_every_recorded_status_carries_a_source_and_a_date(): void
    {
        foreach (OfficialStatus::all() as $status) {
            $this->assertNotNull($status->source, 'Status tanpa sumber.');
            $this->assertNotNull($status->source_url, 'Status tanpa tautan sumber.');
            $this->assertNotNull($status->published_at, 'Status tanpa tanggal terbit.');
            $this->assertNotNull($status->reason, 'Status tanpa alasan.');
        }
    }

    /**
     * §92: ini dicatat dari pemberitaan atas pengumuman, bukan dari kanal pengelola
     * langsung. Menandainya OFFICIAL akan melebihkan otoritasnya.
     */
    public function test_the_source_does_not_overstate_its_authority(): void
    {
        $this->assertSame('ADMIN_VERIFIED', OfficialStatus::firstOrFail()->dataSource->source_type->value);
    }

    /**
     * Status berbatas waktu. Ketika masa berlakunya lewat, sistem kembali menjawab
     * UNKNOWN alih-alih mempertahankan kabar lama sebagai kabar sekarang.
     */
    public function test_a_status_expires_back_into_unknown(): void
    {
        $this->assertSame(OfficialStatusValue::CLOSED, $this->statusTerkini('gunung-semeru'));

        $this->travel(2)->years();

        $this->assertSame(
            OfficialStatusValue::UNKNOWN,
            $this->statusTerkini('gunung-semeru'),
            'Kabar penutupan tahun lalu bukan kabar hari ini.'
        );
    }

    public function test_running_the_seeder_twice_does_not_duplicate(): void
    {
        $sebelum = OfficialStatus::count();

        $this->seed(OfficialStatusSeeder::class);

        $this->assertSame($sebelum, OfficialStatus::count());
    }

    private function statusTerkini(string $slug): OfficialStatusValue
    {
        $gunung = Mountain::where('slug', $slug)->firstOrFail();

        return app(OfficialStatusService::class)->currentValueFor($gunung);
    }
}
