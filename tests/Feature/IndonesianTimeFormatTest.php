<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Support\Timezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Jam dan tanggal di layar mengikuti DESIGN.md bagian Tipografi (W9, sapuan yang ditunda
 * W3-G2 dan W4): jam ditulis "19.33", bukan "19:33", dan tanggal pendek menulis tahunnya bila
 * tanggal itu bukan di tahun berjalan (W3-1g, W4 "'D, j M' tanpa tahun"). Trip Tahun Baru yang
 * direncanakan pada Desember tidak terbaca seperti tanggal dekat di tahun ini.
 *
 * Isian jam (input type=time dan datetime-local) tetap memakai titik dua, karena itu bentuk
 * yang dibaca peramban, bukan teks untuk pendaki.
 */
class IndonesianTimeFormatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nilai isian yang memang memakai titik dua. Selain baris ini, format jam untuk ditampilkan
     * di app/ memakai titik. Aturan validasi (date_format) dan pembacaan isian
     * (createFromFormat) tidak menampilkan apa pun, jadi tidak disapu.
     *
     * @var array<string, string>
     */
    private const JAM_ISIAN = [
        "app/Http/Controllers/Trips/TripController.php|'start_time' => \$trip->start_time ? Carbon::parse(\$trip->start_time)->format('H:i') : ''," => 'nilai isian jam mulai (input type=time)',
        "app/Services/Trips/RencanaTrip.php|'start_time' => \$trip->start_time ? Carbon::parse(\$trip->start_time)->format('H:i') : ''," => 'nilai isian jam mulai sunting trip (input type=time)',
        "app/Services/Trips/PesanRencana.php|'kembali' => ['tanggal' => \$kembali?->toDateString(), 'jam' => \$kembali?->format('H:i')]," => 'nilai isian jam kembali (input type=time)',
        'app/Services/DataResmi/HalamanCatatanStatus.php|' => 'nilai isian datetime-local',
    ];

    /**
     * Tanggal pendek yang sengaja tanpa tahun: semuanya tanggal yang paling lama sebulan lalu,
     * atau daftar yang sudah dipilah per tahun di kepala halamannya.
     *
     * @var array<string, string>
     */
    private const TANGGAL_TANPA_TAHUN = [
        'app/Services/Community/HubProgres.php' => 'riwayat Progres dipilah per tahun; tahunnya di kepala halaman',
        'app/Services/Mountains/HalamanGunung.php' => 'tanggal pendakian laporan komunitas, paling lama 30 hari (conditions.community_recent_days)',
        'app/Services/Trails/RouteComparisonTable.php' => 'tanggal pendakian laporan komunitas, paling lama 30 hari',
        'app/Services/Trails/TautanBagikan.php' => 'tanggal dibuat tautan bagikan yang masih aktif (share_links.ttl_days)',
        'app/Services/Trips/PaketLapangan.php|$l->hike_date' => 'tanggal pendakian laporan komunitas di paket, paling lama 30 hari',
    ];

    public function test_a_clock_time_is_written_with_a_period_and_its_zone(): void
    {
        $instan = Carbon::parse('2026-09-29 12:33:00', 'UTC');

        $this->assertSame('29 Sep 2026 19.33 WIB', Timezone::display($instan, 'Asia/Jakarta'));
        $this->assertSame('29 Sep 2026 20.33 WITA', Timezone::display($instan, 'Asia/Makassar'));
    }

    public function test_a_short_date_names_its_year_only_outside_the_current_year(): void
    {
        $this->travelTo(Carbon::parse('2026-12-20 03:00:00', 'UTC'));

        $this->assertSame('3 Nov', Timezone::tanggalPendek(Carbon::parse('2026-11-03')));
        $this->assertSame('Jum, 1 Jan 2027', Timezone::tanggalPendek(Carbon::parse('2027-01-01'), denganHari: true));
        $this->assertSame('3 Nov 2025', Timezone::tanggalPendek(Carbon::parse('2025-11-03')));
    }

    /**
     * Trip 10 Januari 2027 yang dibuka pada 20 Desember 2026: butir cuacanya menunggu BMKG
     * sampai 7 Januari 2027, dan tahunnya tertulis.
     */
    public function test_a_new_year_trip_names_the_year_it_waits_for(): void
    {
        $this->travelTo(Carbon::parse('2026-12-20 03:00:00', 'UTC'));
        $trip = TripPlan::factory()->create(['trail_id' => Trail::factory()->published()->create()->id, 'planned_date' => '2027-01-10']);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::WEATHER->value,
            'label' => 'Memeriksa prakiraan cuaca terbaru',
            'is_critical' => true,
            'status' => PreparationStatus::NOT_CONFIRMED->value,
        ]);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props'];
        $cuaca = collect($props['kategori'])->firstWhere('kunci', PreparationCategory::WEATHER->value);

        $this->assertSame('Menunggu BMKG: terbit mulai Kam, 7 Jan 2027', $cuaca['items'][0]['menunggu']);
    }

    public function test_no_screen_writes_a_clock_time_with_a_colon(): void
    {
        $pelanggar = [];

        foreach ($this->barisPhp() as [$jalur, $baris]) {
            $jamTertulis = (bool) preg_match('/pukul (?:%02d|\d{2}):\d{2}/', $baris);

            if (! $jamTertulis && (! preg_match("/'[^']*H:i[^']*'/", $baris) || str_contains($baris, 'H:i:s') || str_contains($baris, 'date_format:') || str_contains($baris, 'createFromFormat('))) {
                continue;
            }

            if (! isset(self::JAM_ISIAN[$jalur.'|'.$baris]) && ! isset(self::JAM_ISIAN[$jalur.'|'])) {
                $pelanggar[] = $jalur.': '.$baris;
            }
        }

        $this->assertSame([], $pelanggar, 'Jam di layar memakai titik ("19.33"), bukan titik dua (DESIGN.md bagian Tipografi).');
    }

    public function test_every_short_date_goes_through_the_one_helper_or_says_why_not(): void
    {
        $pelanggar = [];

        foreach ($this->barisPhp() as [$jalur, $baris]) {
            if (! preg_match("/translatedFormat\\('(?:D, )?j M'\\)/", $baris)) {
                continue;
            }

            $diizinkan = isset(self::TANGGAL_TANPA_TAHUN[$jalur])
                || collect(self::TANGGAL_TANPA_TAHUN)->keys()->contains(fn (string $kunci) => str_contains($kunci, '|') && str_starts_with($kunci, $jalur.'|') && str_contains($baris, explode('|', $kunci)[1]));

            if (! $diizinkan) {
                $pelanggar[] = $jalur.': '.$baris;
            }
        }

        $this->assertSame([], $pelanggar, 'Tanggal pendek ditulis lewat Timezone::tanggalPendek(), yang menulis tahun di luar tahun berjalan.');
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function barisPhp(): array
    {
        $hasil = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            if ($berkas->getExtension() !== 'php') {
                continue;
            }

            $jalur = 'app/'.str_replace('\\', '/', $berkas->getRelativePathname());

            foreach (preg_split('/\R/', $berkas->getContents()) as $baris) {
                $hasil[] = [$jalur, trim($baris)];
            }
        }

        return $hasil;
    }
}
