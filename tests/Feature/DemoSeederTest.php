<?php

namespace Tests\Feature;

use App\Enums\SourceType;
use App\Enums\TripStatus;
use App\Enums\VerificationStatus;
use App\Exceptions\DataContohDitolak;
use App\Models\DataSource;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\PenjagaDataContoh;
use App\Support\Timezone;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Data contoh berlabel (DK-35). Dijalankan di SQLite dalam memori milik suite, tidak di
 * berkas atau basis data mana pun.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Isinya meniru cerita di prototype pemilik: Budi, trip "Gede via Cibodas" lima belas
     * hari lagi, tiga pendakian selesai, satu jalur menunggu data.
     */
    public function test_the_sample_tells_the_prototype_story(): void
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->assertSame('Budi Setiawan', $budi->name);
        $this->assertTrue($budi->hasCompletedProfile());
        $this->assertSame('Gede via Cibodas', $trip->name);
        $this->assertSame(Carbon::parse(Timezone::earliestDateInIndonesia())->addDays(15)->toDateString(), $trip->planned_date->toDateString());
        $this->assertSame(8, Mountain::count());
        $this->assertSame(10, Trail::where('is_published', true)->count());
        $this->assertSame(1, Trail::where('is_published', false)->count());
        $this->assertSame(1, TripPlan::where('status', TripStatus::CANCELLED->value)->count());
        $this->assertSame(3, HikingHistory::where('user_id', $budi->id)->count());
    }

    /**
     * Setiap teks yang dapat terbaca sebagai fakta membawa penanda DATA CONTOH.
     */
    public function test_every_text_that_reads_as_fact_is_marked(): void
    {
        $this->seed(DemoSeeder::class);

        $tanpaPenanda = [
            ...DataSource::pluck('source_name')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA))->all(),
            ...Mountain::pluck('description')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            ...Trail::pluck('description')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            ...OfficialStatus::pluck('source')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA))->all(),
            // Tinjauan W1-9a #5: reason juga terbaca sebagai fakta ("Perbaikan jembatan",
            // "Penutupan musiman") dan karenanya wajib membawa penanda yang sama.
            ...OfficialStatus::whereNotNull('reason')->pluck('reason')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            ...TripPlan::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
        ];

        $this->assertSame([], $tanpaPenanda);
    }

    /**
     * Tinjauan W1-9a #7: koneksi pgsql config/database.php membaca host, user, dan
     * sandi nyata dari .env. Sebelumnya test ini memakai koneksi itu apa adanya lewat
     * DB::setDefaultConnection('pgsql'); konfigurasinya di sini diganti seluruhnya
     * dengan nilai buangan yang tidak dapat menjangkau host mana pun, supaya perubahan
     * kode di masa depan yang kebetulan menjalankan query sebelum pemeriksaan pengemudi
     * gagal cepat dan jelas, bukan diam-diam menjangkau basis data produksi.
     */
    public function test_it_refuses_a_database_that_is_not_sqlite(): void
    {
        config(['database.connections.pgsql' => [
            'driver' => 'pgsql',
            'host' => 'tidak-ada.invalid',
            'port' => 5432,
            'database' => 'tidak_ada',
            'username' => 'tidak_ada',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
        ]]);

        $bawaan = DB::getDefaultConnection();
        DB::purge('pgsql');
        DB::setDefaultConnection('pgsql');

        try {
            $this->expectException(DataContohDitolak::class);
            $this->expectExceptionMessage('SQLite');

            $this->app->make(DemoSeeder::class)->run();
        } finally {
            DB::setDefaultConnection($bawaan);
            DB::purge('pgsql');
        }
    }

    public function test_it_refuses_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(DataContohDitolak::class);
        $this->expectExceptionMessage('produksi');

        $this->app->make(DemoSeeder::class)->run();
    }

    /**
     * DatabaseSeeder tidak pernah menanam data contoh, langsung maupun lewat seeder lain
     * yang dipanggilnya (tinjauan akhir W1, M-8). Versi sebelumnya mencari kata
     * "DemoSeeder" di sumbernya, sehingga pemanggilan tidak langsung lolos; kini seeder
     * dijalankan sungguhan di SQLite memori suite, lalu setiap kolom yang diberi penanda
     * oleh DemoSeeder diperiksa.
     */
    public function test_the_regular_seeder_never_plants_sample_data(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Mountain::count(), 'DatabaseSeeder tidak mengisi apa pun; test ini tidak membuktikan apa pun.');

        $bertanda = [];

        foreach ([
            'data_sources' => ['source_name', 'notes'],
            'mountains' => ['description'],
            'trails' => ['description'],
            'official_statuses' => ['source', 'reason'],
            'trip_plans' => ['notes'],
        ] as $tabel => $kolom) {
            foreach ($kolom as $satu) {
                $jumlah = DB::table($tabel)->where($satu, 'like', DemoSeeder::PENANDA.'%')->count();

                if ($jumlah > 0) {
                    $bertanda[] = "{$tabel}.{$satu}: {$jumlah} baris";
                }
            }
        }

        $this->assertSame([], $bertanda, 'DatabaseSeeder menanam data contoh: '.implode(', ', $bertanda));
        $this->assertDatabaseMissing('users', ['email' => DemoSeeder::EMAIL_PENDAKI]);
    }

    /**
     * Pagar sumber pelengkap (tinjauan ulang W1, N-2): test perilaku di atas berjalan di
     * APP_ENV=testing, sehingga pemanggilan DemoSeeder yang dibatasi ke lingkungan lain akan
     * lolos. DatabaseSeeder tidak menyebut DemoSeeder sama sekali.
     */
    public function test_the_regular_seeder_source_never_names_the_demo_seeder(): void
    {
        $this->assertStringNotContainsString('DemoSeeder', File::get(database_path('seeders/DatabaseSeeder.php')));
    }

    /**
     * Kata sandi pendaki contoh diacak setiap kali dan hanya dicetak ke konsol; tidak ada
     * kata sandi yang tertulis di repo (DatabaseSeeder lama memakai "password", HANDOVER
     * bagian 0 butir 10).
     */
    public function test_the_sample_password_is_random_and_never_written_down(): void
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $sumber = File::get(database_path('seeders/DemoSeeder.php'));

        $this->assertFalse(Hash::check('password', $budi->password));
        $this->assertStringContainsString('Str::password(20, symbols: false)', $sumber);
        $this->assertDoesNotMatchRegularExpression("/'password' => '[^']+'/", $sumber);
    }

    /**
     * Tinjauan W1-9a #6: verification_status UNVERIFIED yang berdampingan dengan
     * verified_at terisi bertentangan secara harfiah. source_type ADMIN_VERIFIED tetap:
     * itu klasifikasi otoritas sumbernya (siapa yang menulis), bukan status verifikasi
     * catatan itu sendiri.
     */
    public function test_the_source_metadata_is_internally_consistent(): void
    {
        $this->seed(DemoSeeder::class);

        $sumber = DataSource::sole();

        $this->assertSame(VerificationStatus::UNVERIFIED, $sumber->verification_status);
        $this->assertNull($sumber->verified_at);
        $this->assertSame(SourceType::ADMIN_VERIFIED, $sumber->source_type);
    }

    /**
     * Tinjauan W1-9a #1(a), bukti empiris seperti yang dipakai peninjau.
     *
     * Store cache "database" dipaksa ter-resolve sekarang dan terikat ke koneksi
     * bukan_demo, meniru store yang sudah pernah dipakai sebelum koneksi basis data
     * default dipindah ke demo (persis penyebab temuan: 10 DELETE pada koneksi bukan
     * demo). putuskanSaluranLuar() wajib memutus ikatan itu sebelum seeding berjalan,
     * supaya Cache::forget() yang dipanggil OfficialStatus::saved() selama seeding tidak
     * pernah menyentuh koneksi tersebut.
     */
    public function test_seeding_never_touches_a_non_demo_cache_connection(): void
    {
        config(['database.connections.bukan_demo' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('bukan_demo')->create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        config([
            'cache.stores.database.connection' => 'bukan_demo',
            'cache.default' => 'database',
        ]);

        // Memaksa store "database" ter-resolve sekarang, terikat ke bukan_demo, persis
        // seperti store yang sudah pernah dipakai sebelum migrate:fresh/db:seed
        // memindahkan koneksi default ke demo pada pemakaian nyata.
        Cache::store();

        DB::connection('bukan_demo')->enableQueryLog();

        (new PenjagaDataContoh)->putuskanSaluranLuar();

        $this->seed(DemoSeeder::class);

        $this->assertSame([], DB::connection('bukan_demo')->getQueryLog());
    }
}
