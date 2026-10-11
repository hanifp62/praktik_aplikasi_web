<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\PendakianAktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Strip pendakian aktif (spec v3 bagian 5.1; rencana induk W2 butir 4).
 *
 * Selama trip berlangsung, setiap halaman berkerangka membawa strip di atas bilah tab yang
 * kembali ke mode lapangan. Datanya props bersama pendakianAktif: hanya milik pemiliknya,
 * tidak dihitung di mode lapangan itu sendiri, dan satu kueri berapa pun jumlah trip dan pos.
 */
class ActiveHikeStripTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_carries_the_hike_on_every_page(): void
    {
        [$budi, $trip, $pos] = $this->pendakianBerlangsung();

        foreach (['/trails', '/dashboard', '/progres'] as $alamat) {
            $this->actingAs($budi)->get($alamat)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('pendakianAktif.id', $trip->id)
                    ->where('pendakianAktif.jalur', $trip->trail->name)
                    ->where('pendakianAktif.posBerikutnya', $pos->name)
                    ->where('pendakianAktif.mulai', '2026-09-27T00:30:00+00:00')
                    ->where('pendakianAktif.jedaSejak', null)
                    ->where('pendakianAktif.jedaDetik', 0)
                    ->where('pendakianAktif.url', '/trips/'.$trip->id.'/hike'));
        }
    }

    /**
     * Ruling W6-FD (M-1 tinjauan akhir W6): strip membawa keadaan jeda sesi dari kueri yang sama,
     * supaya waktunya waktu bergerak seperti mode lapangan dan berhenti selama dijeda. Instannya
     * ISO 8601 UTC seperti mulai.
     */
    public function test_the_strip_carries_the_pause_state_of_the_session(): void
    {
        [$budi, $trip] = $this->pendakianBerlangsung();
        $trip->hikingSession->update([
            'paused_at' => Carbon::parse('2026-09-27 03:00:00', 'UTC'),
            'paused_seconds' => 1800,
            'status' => HikingSessionStatus::PAUSED->value,
        ]);

        $this->assertSame(
            ['jedaSejak' => '2026-09-27T03:00:00+00:00', 'jedaDetik' => 1800],
            array_intersect_key(app(PendakianAktif::class)->untuk($budi), ['jedaSejak' => true, 'jedaDetik' => true]),
        );
    }

    public function test_another_hiker_never_sees_it(): void
    {
        $this->pendakianBerlangsung();

        $this->actingAs(User::factory()->create())->get('/trails')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif', null));
    }

    public function test_a_guest_on_a_public_page_carries_no_active_hike(): void
    {
        $this->pendakianBerlangsung();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif', null));
    }

    public function test_field_mode_itself_does_not_compute_it(): void
    {
        [$budi, $trip] = $this->pendakianBerlangsung();

        $this->actingAs($budi)->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike')->where('pendakianAktif', null));
    }

    public function test_planned_finished_and_cancelled_trips_are_not_an_active_hike(): void
    {
        $budi = User::factory()->create();

        foreach ([TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE, TripStatus::COMPLETED, TripStatus::CANCELLED] as $status) {
            TripPlan::factory()->for($budi)->create(['status' => $status->value]);
        }

        $this->actingAs($budi)->get('/trails')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif', null));
    }

    /**
     * Trip dimulai tanpa sesi atau sebelum posisi pertama dikirim: strip tetap menyebut
     * jalurnya, dan yang belum diketahui tetap kosong, bukan ditebak.
     */
    public function test_a_hike_without_a_session_still_names_its_trail(): void
    {
        $budi = User::factory()->create();
        $trip = TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value]);

        $this->assertSame([
            'id' => $trip->id,
            'jalur' => $trip->trail->name,
            'posBerikutnya' => null,
            'mulai' => null,
            'jedaSejak' => null,
            'jedaDetik' => null,
            'url' => '/trips/'.$trip->id.'/hike',
        ], app(PendakianAktif::class)->untuk($budi));
    }

    public function test_the_lookup_costs_one_query_whatever_the_number_of_trips_and_checkpoints(): void
    {
        [$budi, $trip] = $this->pendakianBerlangsung();

        for ($i = 2; $i < 30; $i++) {
            Checkpoint::factory()->for($trip->trail)->create(['sequence' => $i]);
        }

        TripPlan::factory()->count(20)->for($budi)->create(['status' => TripStatus::COMPLETED->value]);

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $this->assertSame($trip->id, app(PendakianAktif::class)->untuk($budi)['id'] ?? null);
        $this->assertSame(1, $kueri);
    }

    /**
     * Putaran perbaikan 1 (W2-4a), Minor 1a. Prop pendakianAktif ditulis closure malas
     * (Step 4): muat ulang parsial yang tidak memintanya tidak boleh sekadar menyembunyikannya
     * dari respons, kueri join-nya sendiri tidak boleh berjalan sama sekali.
     */
    public function test_a_partial_reload_that_does_not_ask_for_it_skips_the_join_query(): void
    {
        [$budi] = $this->pendakianBerlangsung();

        $respons = $this->actingAs($budi)->get('/dashboard');

        $kueri = [];
        DB::listen(function ($query) use (&$kueri) {
            $kueri[] = $query->sql;
        });

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('title', fn (AssertableInertia $muatUlang) => $muatUlang->missing('pendakianAktif')));

        $ikutHikingSessions = collect($kueri)->filter(fn (string $sql) => str_contains($sql, 'hiking_sessions'));

        $this->assertTrue($ikutHikingSessions->isEmpty(), 'Muat ulang parsial yang tidak meminta pendakianAktif menjalankan: '.$ikutHikingSessions->implode(' | '));
    }

    /**
     * Putaran perbaikan 1 (W2-4a), Minor 1b. Kontrol pasangan test di atas: muat ulang
     * parsial yang memang meminta pendakianAktif tetap satu kueri, sama seperti muat penuh
     * (Step 3).
     */
    public function test_a_partial_reload_that_asks_for_it_runs_exactly_one_query(): void
    {
        [$budi, $trip] = $this->pendakianBerlangsung();

        $respons = $this->actingAs($budi)->get('/dashboard');

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('pendakianAktif', fn (AssertableInertia $muatUlang) => $muatUlang->where('pendakianAktif.id', $trip->id)));

        $this->assertSame(1, $kueri);
    }

    /**
     * Strip di atas bilah tab, satu tautan 48 px ke mode lapangan, dan app.css tahu
     * keberadaannya untuk menggeser bar aksi dan toast di atasnya.
     */
    public function test_the_strip_sits_above_the_tab_bar_and_opens_field_mode(): void
    {
        $strip = File::get(resource_path('js/layouts/StripPendakian.tsx'));
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('<aside ref={wadah} data-strip-pendakian="" aria-label="Pendakian berlangsung" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--bilah-tab)+0.5rem)] z-30 px-4">', $strip);
        $this->assertSame(1, substr_count($strip, '<aside '), 'Strip adalah satu landmark aside, bukan berulang.');
        $this->assertStringContainsString("akar.style.setProperty('--strip-pendakian', `\${elemen.offsetHeight + 8}px`)", $strip, 'Tinggi strip yang membungkus diukur, bukan ditebak.');
        $this->assertStringContainsString('new ResizeObserver(ukur)', $strip);
        $this->assertStringContainsString("akar.style.removeProperty('--strip-pendakian');", $strip);
        $this->assertStringContainsString('href={pendakian.url}', $strip);
        $this->assertStringContainsString('min-h-12', $strip);
        $this->assertStringContainsString('<span className="sr-only">Pendakian berlangsung: </span>', $strip);
        $this->assertMatchesRegularExpression('/>Buka<\/span>/', $strip);
        $this->assertStringNotContainsString('aria-live', $strip, 'Waktu berjalan tidak diumumkan setiap menit.');
        // Ruling W6-FD: waktu bergerak dari fungsi mode lapangan, bukan hitungan sendiri dari mulai.
        $this->assertStringContainsString('pendakian.mulai ? keteranganBerjalan(jedaSejak !== null, teksLama(detikBergerak(Date.parse(pendakian.mulai), pendakian.jedaDetik ?? 0, jedaSejak, sekarang))) : null,', $strip);
        $this->assertStringContainsString('const jedaSejak = pendakian.jedaSejak ? Date.parse(pendakian.jedaSejak) : null;', $strip);
        $this->assertStringContainsString('{props.pendakianAktif ? <StripPendakian pendakian={props.pendakianAktif} /> : null}', $layout);
        $this->assertGreaterThan(strpos($layout, '</main>'), strpos($layout, '<StripPendakian '), 'Strip dijangkau sesudah isi halaman.');
        $this->assertLessThan(strpos($layout, '<BilahTab '), strpos($layout, '<StripPendakian '), 'Strip dijangkau sebelum bilah tab, sama dengan letaknya di layar.');
        $this->assertMatchesRegularExpression('/:root:has\(\[data-strip-pendakian\]\) \{\s*--strip-pendakian: 3\.5rem;/', File::get(resource_path('css/app.css')));
        $this->assertStringNotContainsString('StripPendakian', File::get(resource_path('js/components/trips/TataLetakPendakian.tsx')), 'Mode lapangan tidak memasang strip.');
    }

    /**
     * Ruling W6-FJ (K-1 gelombang perbaikan akhir W6): strip ada di setiap halaman berkerangka, jadi ia mengimpor
     * waktu bergerak dari lib/waktuBergerak.ts, bukan dari hitung.ts atau kalimatSumber.ts. Impor itu menarik kedua modul
     * mode lapangan ke potongan tata letak (+3,0 kB gzip di setiap halaman, terukur di laporan). hitung.ts dan
     * kalimatSumber.ts mengekspor ulang fungsi yang sama, jadi sumbernya tetap satu; identitasnya diperiksa di Node.
     */
    public function test_the_strip_takes_moving_time_from_the_small_module_only(): void
    {
        $strip = File::get(resource_path('js/layouts/StripPendakian.tsx'));

        $this->assertStringContainsString("import { detikBergerak, keteranganBerjalan, teksLama } from '@/lib/waktuBergerak';", $strip);
        $this->assertDoesNotMatchRegularExpression("/from '[^']*(?:mendaki\/hitung|luring\/kalimatSumber)'/", $strip, 'Strip tidak boleh menarik modul mode lapangan ke potongan tata letak.');
        $this->assertDoesNotMatchRegularExpression('/^import (?!type\b)/m', File::get(resource_path('js/lib/waktuBergerak.ts')), 'Modul waktu bergerak tanpa impor (ruling L3).');

        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const waktu = await import(pathToFileURL('.json_encode(resource_path('js/lib/waktuBergerak.ts')).').href);'
            .'const hitung = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/hitung.ts')).').href);'
            .'const sumber = await import(pathToFileURL('.json_encode(resource_path('js/luring/kalimatSumber.ts')).').href);'
            .'process.stdout.write(JSON.stringify([hitung.teksLama === waktu.teksLama, hitung.detikBergerak === waktu.detikBergerak, sumber.keteranganBerjalan === waktu.keteranganBerjalan, waktu.keteranganBerjalan(true, waktu.teksLama(waktu.detikBergerak(0, 7200, 12600000, 18000000)))]));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal: '.mb_substr($proses->getErrorOutput(), 0, 600));
        $this->assertSame([true, true, true, 'Dijeda, 1 jam 30 menit bergerak'], json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * Budi sedang mendaki: trip IN_PROGRESS, sesi dimulai 27 September 2026 00.30 UTC, dan
     * sesi sedang menuju Pos 1.
     *
     * @return array{0: User, 1: TripPlan, 2: Checkpoint}
     */
    private function pendakianBerlangsung(): array
    {
        $budi = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Gede via Cibodas']);
        $pos = Checkpoint::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Telaga Biru']);
        $trip = TripPlan::factory()->for($budi)->for($trail)->create(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'current_checkpoint_id' => $pos->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => Carbon::parse('2026-09-27 00:30:00', 'UTC'),
        ]);

        return [$budi, $trip, $pos];
    }
}
