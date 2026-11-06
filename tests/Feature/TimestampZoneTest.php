<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * PRD §93, dan aturan kesembilan di ARCHITECTURE.md: waktu disimpan UTC, ditampilkan
 * dalam zona gunungnya, lengkap dengan penanda WIB, WITA, atau WIT.
 *
 * Aturannya sudah tertulis dan dijaga untuk cuaca, tetapi tiga stempel waktu lain lolos:
 * perhitungan kesiapan, waktu rekomendasi dihasilkan, dan jejak audit. Semuanya
 * dirender tanpa zona sama sekali.
 *
 * Jam tanpa penanda zona di Indonesia bukan ketelitian yang kurang, melainkan angka yang
 * tidak dapat dibaca: selisihnya sampai dua jam antara Sabang dan Jayapura.
 */
class TimestampZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_readiness_calculation_time_carries_its_zone(): void
    {
        $trip = $this->trip('Asia/Jakarta');
        app(ReadinessService::class)->record($trip);

        $this->actingAs($trip->user)
            ->get('/trips/'.$trip->id.'/readiness')
            ->assertOk()
            ->assertSee('WIB');
    }

    /**
     * Gunung di Lombok memakai WITA. Menampilkan jamnya sebagai WIB menggeser seluruh
     * perhitungan satu jam.
     */
    public function test_it_follows_the_mountain_not_the_server(): void
    {
        $trip = $this->trip('Asia/Makassar');
        app(ReadinessService::class)->record($trip);

        $this->actingAs($trip->user)
            ->get('/trips/'.$trip->id.'/readiness')
            ->assertOk()
            ->assertSee('WITA');
    }

    /**
     * Ruling FF-1 (uji E2E akhir F-1), sapuan app/: halaman admin tidak berkonteks satu jalur (daftarnya mencampur
     * gunung, jalur, segmen, dan sumber data tanpa jalur), jadi instan UTC ditulis sebagai tanggal kalender WIB,
     * Timezone::DEFAULT, sama dengan TautanBagikan. Instan 18.30 UTC sudah tanggal berikutnya di WIB; tanggal UTC-nya
     * berarti satu hari lebih awal.
     */
    public function test_admin_pages_write_instants_as_calendar_days_in_wib(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 03:00:00', 'UTC'));
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]));

        OfficialStatus::factory()->create([
            'statusable_id' => Trail::factory()->create(['name' => 'Jalur Kedaluwarsa'])->id,
            'effective_at' => '2026-10-01 18:30:00',
            'expires_at' => '2026-10-11 18:30:00',
            'verified_at' => '2026-10-11 18:30:00',
        ]);
        OfficialStatus::factory()->create([
            'statusable_id' => Trail::factory()->create(['name' => 'Jalur Basi'])->id,
            'verified_at' => '2026-06-30 18:30:00',
        ]);
        DataSource::factory()->create(['verified_at' => '2026-10-11 18:30:00']);
        PermitRequirement::factory()->create(['trail_id' => Trail::factory()->create()->id, 'verified_at' => '2026-10-11 18:30:00']);

        $status = $this->get(route('admin.statuses'))->assertOk()->viewData('page')['props'];
        $riwayat = collect($status['records']['data'])->keyBy('objek');

        $this->assertSame('02 Okt 2026 – 12 Okt 2026', $riwayat['Jalur Kedaluwarsa']['berlaku']);
        $this->assertSame('12 Okt 2026', $riwayat['Jalur Kedaluwarsa']['diverifikasi']);
        $this->assertSame([['Jalur Kedaluwarsa', 'berlaku sampai 12 Okt 2026']], array_map(fn (array $b) => [$b['nama'], $b['keterangan']], $status['tinjau']['kedaluwarsa']));
        $this->assertSame([['Jalur Basi', 'terakhir diverifikasi 01 Jul 2026']], array_map(fn (array $b) => [$b['nama'], $b['keterangan']], $status['tinjau']['basi']));

        $this->get(route('admin.sources'))->assertInertia(fn (AssertableInertia $page) => $page->where('sources.data.0.verifiedAt', '12 Okt 2026'));
        $this->get(route('admin.permits'))->assertInertia(fn (AssertableInertia $page) => $page->where('rules.data.0.diverifikasi', '12 Okt 2026'));
    }

    /**
     * Sapuan ruling FF-1: kolom instan (`*_at`, disimpan UTC) yang langsung diformat dengan translatedFormat(),
     * format(), atau isoFormat() menulis tanggal dan jam UTC server, sehari lebih awal untuk pendakian yang selesai
     * antara 00.00 dan 07.00 WIB. Bentuk yang benar menyalin lalu memindah zonanya lebih dulu
     * (`->copy()->setTimezone(...)`) atau memakai Timezone::display(). Sapuan jam di atas hanya mengenali format jam,
     * jadi format tanggal saja ("d M Y") lolos dari situ.
     *
     * Pengecualian hanya kolom bercast 'date' (tanggal kalender, bukan instan), disebut per berkas: nama kolomnya
     * sama dengan kolom instan model lain (expires_at milik OfficialStatus adalah datetime).
     */
    public function test_no_instant_column_is_formatted_without_its_zone(): void
    {
        $tanggalKalender = [
            'Http/Controllers/Community/TrailContributionController.php' => ['expires_at'],
            'Http/Resources/Admin/CredentialResource.php' => ['issued_at', 'expires_at'],
        ];
        $pelanggar = [];

        foreach ([...File::allFiles(app_path()), ...File::allFiles(resource_path('views'))] as $berkas) {
            $relatif = str_replace('\\', '/', $berkas->getRelativePathname());

            foreach (file($berkas->getPathname()) as $nomor => $baris) {
                preg_match_all('/->(\w+_at)\??->(?:translatedFormat|format|isoFormat)\(/', $baris, $cocok);

                foreach ($cocok[1] as $kolom) {
                    if (! in_array($kolom, $tanggalKalender[$relatif] ?? [], true)) {
                        $pelanggar[] = $relatif.':'.($nomor + 1).' '.$kolom;
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Kolom instan diformat tanpa zona di: '.implode(', ', $pelanggar));
    }

    /**
     * Sapuan menyeluruh: tidak boleh ada lagi jam yang dirender tanpa penanda zona di
     * berkas tampilan mana pun.
     */
    public function test_no_view_renders_a_bare_clock_time(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();

            // TA-m2 (tinjauan audit): BF-1 membuat seluruh jam tampil memakai "H.i"
            // bertitik, jadi pola yang hanya mengenali "H:i" buta terhadap konvensi baru.
            if (! preg_match('/(?:translatedFormat|format)\([\'"][^\'"]*H[.:]i/', $isi)) {
                continue;
            }

            // Zonanya boleh disebut sekali untuk seluruh tabel, seperti judul kolom
            // "Waktu (WIB)", alih-alih diulang di tiap baris. Yang dilarang adalah jam
            // yang zonanya tidak disebut di mana pun pada halaman itu.
            $menyebutZona = str_contains($isi, 'Timezone::display')
                || str_contains($isi, 'Timezone::label')
                || str_contains($isi, 'timezone_label');

            if (! $menyebutZona) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Jam tanpa penanda zona di: '.implode(', ', $pelanggar)
        );
    }

    /**
     * Sapuan lain: now(), today(), atau Carbon::now() mentah membaca "hari ini"
     * menurut zona aplikasi (UTC, config/app.php), dan tidak ada pengguna yang hidup
     * di UTC. Dipakai untuk aritmetika hari lewat diffInDays()/whereDate(), ia salah
     * selama tujuh sampai sembilan jam setiap hari (lihat docblock App\Support\Timezone).
     *
     * Menggrep "Asia/Jakarta" ke seluruh app/ pernah dicoba dan terlalu berisik:
     * string itu juga muncul sebagai data domain (zona gunung tersimpan, seeder).
     *
     * Sapuan ini melacak PROPERTI-nya (variabel yang isinya berasal dari now()/today()
     * mentah), bukan hanya kejadian sebaris. Perbaikan zona waktu di kelas cacat ini
     * sendiri mengadopsi bentuk dua baris -- `$today = Carbon::parse(...)` lalu
     * `->diffInDays(...)` di baris lain -- dan bentuk itu lolos begitu saja dari
     * pemeriksaan sebaris yang lama: penjaga yang menangkap ejaan cacat kemarin, bukan
     * bentuknya besok. Variabel yang diisi dari now()/today()/Carbon::now() TANPA
     * menyebut Timezone:: pada baris yang sama ditandai mentah; ditandai bersih lagi
     * begitu ditulis ulang lewat Timezone:: (mengikuti pola perbaikan yang sudah ada);
     * dan pemakaian ->diffInDays()/whereDate() pada variabel yang masih bertanda mentah
     * di baris manapun pada berkas yang sama tertangkap.
     *
     * Keterbatasan yang diketahui, bukan disembunyikan: pelacakannya linear per
     * berkas, bukan sadar cabang/method. Nama variabel yang dipakai ulang untuk dua
     * hal berbeda di method lain pada berkas yang sama, salah satunya mentah, bisa
     * memicu tangkapan yang keliru. Nilai tukarnya diterima: linear-per-berkas ini
     * sudah menutup lubang nyata (bentuk dua baris) tanpa memerlukan penebak AST penuh.
     */
    public function test_no_day_level_date_comparison_calls_now_or_today_directly(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            $baris = file($berkas->getPathname());
            $variabelMentah = [];

            foreach ($baris as $nomor => $satu) {
                if (preg_match('/\b(?:diffInDays|whereDate)\s*\(/', $satu)
                    && preg_match('/\b(?:now|today)\s*\(\)|Carbon::now\s*\(\)/', $satu)
                    && ! str_contains($satu, 'Timezone::')) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }

                // Ditandai mentah: variabel diisi now()/today()/Carbon::now() tanpa
                // Timezone:: pada baris yang sama.
                if (preg_match('/\$(\w+)\s*=.*(?:\bnow\s*\(\)|\btoday\s*\(\)|Carbon::now\s*\(\))/', $satu, $cocok)
                    && ! str_contains($satu, 'Timezone::')) {
                    $variabelMentah[$cocok[1]] = true;
                }

                // Ditandai bersih lagi: variabel yang sama ditulis ulang lewat
                // Timezone::, mengikuti pola perbaikan yang sudah dipakai di kode ini.
                if (preg_match('/\$(\w+)\s*=.*Timezone::/', $satu, $cocok)) {
                    unset($variabelMentah[$cocok[1]]);
                }
            }

            if ($variabelMentah === []) {
                continue;
            }

            foreach ($baris as $nomor => $satu) {
                foreach (array_keys($variabelMentah) as $nama) {
                    $pola = '/\$'.preg_quote($nama, '/').'\s*->\s*diffInDays\s*\(|whereDate\s*\([^)]*\$'.preg_quote($nama, '/').'\b/';

                    if (preg_match($pola, $satu)) {
                        $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                    }
                }
            }
        }

        $pelanggar = array_values(array_unique($pelanggar));

        $this->assertSame(
            [],
            $pelanggar,
            'diffInDays()/whereDate() memakai now()/today() mentah (langsung atau lewat variabel), bukan App\Support\Timezone, di: '.implode(', ', $pelanggar)
        );
    }

    private function trip(string $timezone): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        $gunung = Mountain::factory()->create(['timezone' => $timezone]);

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->for($gunung)->create()->id,
            'name' => 'Uji zona waktu',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ])->fresh(['user', 'trail.mountain']);
    }
}
