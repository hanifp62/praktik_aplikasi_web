<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
     * Sapuan menyeluruh: tidak boleh ada lagi jam yang dirender tanpa penanda zona di
     * berkas tampilan mana pun.
     */
    public function test_no_view_renders_a_bare_clock_time(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();

            if (! preg_match('/(?:translatedFormat|format)\([\'"][^\'"]*H:i/', $isi)) {
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
