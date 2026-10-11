<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\TrailNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kabar jalur.
 *
 * Satu-satunya Trigger dalam kerangka Fogg di aplikasi ini: seluruh layar lain melayani
 * seseorang yang sudah memutuskan merencanakan pendakian, sedangkan ini memberi alasan
 * membukanya di antara dua pendakian.
 *
 * Isinya keadaan gunung, bukan aktivitas orang. Halamannya React (Inertia): yang diuji
 * adalah props yang dikirim server, dan teks yang dirender klien dijaga dari sumbernya.
 */
class TrailNewsTest extends TestCase
{
    use RefreshDatabase;

    private function gunungBerjalur(string $nama): array
    {
        $gunung = Mountain::factory()->create(['name' => $nama]);

        return [$gunung, $this->jalurTerbit($gunung, 'Jalur '.$nama)];
    }

    /**
     * Jalur terbit tanpa status bawaan factory.
     *
     * state published() menambahkan satu status "belum diketahui" supaya gerbang
     * publikasi terpenuhi; status itu disingkirkan di sini agar setiap test mengukur
     * kabar yang ia buat sendiri. Gerbang berjalan pada event penyimpanan jalur, jadi
     * test yang masih perlu mengubah jalur ini memakai saveQuietly().
     */
    private function jalurTerbit(Mountain $gunung, string $nama): Trail
    {
        $trail = Trail::factory()->easy()->published()->for($gunung)->create(['name' => $nama]);
        $trail->officialStatuses()->delete();

        return $trail->fresh();
    }

    private function tutup(Trail $trail, string $alasan): OfficialStatus
    {
        return OfficialStatus::create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Besar TN',
            'effective_at' => now()->subDay(),
            'reason' => $alasan,
        ]);
    }

    private function laporan(Trail $trail, ModerationStatus $status, string $catatan = 'Jembatan Pos 3 putus.'): TrailConditionReport
    {
        return TrailConditionReport::factory()->for($trail)->create([
            'user_id' => User::factory()->create(['name' => 'Rani'])->id,
            'moderation_status' => $status->value,
            'note' => $catatan,
        ]);
    }

    private function kabar(User $user)
    {
        return app(TrailNewsService::class)->forUser($user);
    }

    private function ikuti(User $user, Mountain $gunung): void
    {
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);
    }

    private function halaman(User $user): TestResponse
    {
        return $this->actingAs($user)->get(route('news'))->assertOk();
    }

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Community/TrailNews.tsx'));
    }

    /**
     * Aksinya kini milik halaman jalur (rute mountains.follow); yang dijaga di sini
     * adalah bahwa mengikuti dan berhenti mengikuti sampai ke kabar.
     */
    public function test_a_hiker_can_follow_and_unfollow_a_mountain(): void
    {
        [$gunung] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('mountains.follow', $gunung), ['ya' => true]);

        $this->assertTrue(app(TrailNewsService::class)->follows($user, $gunung));

        $this->actingAs($user)->post(route('mountains.follow', $gunung), ['ya' => false]);

        $this->assertFalse(app(TrailNewsService::class)->follows($user->fresh(), $gunung));
    }

    public function test_the_feed_carries_status_changes_and_reports_from_followed_mountains(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);

        $kabar = $this->kabar($user);

        $this->assertCount(2, $kabar);
        // Diurutkan abjad sebelum dibandingkan supaya test ini tidak ikut menguji
        // urutan waktunya; urutan itu punya testnya sendiri.
        $this->assertSame(
            [TrailNewsService::LAPORAN, TrailNewsService::STATUS],
            $kabar->pluck('jenis')->sort()->values()->all()
        );
    }

    /**
     * Gunung yang tidak diikuti tidak boleh masuk. Umpan yang memuat segalanya berhenti
     * menjadi alasan membuka aplikasi dan menjadi alasan mematikan pemberitahuan.
     */
    public function test_an_unfollowed_mountain_never_appears(): void
    {
        [$diikuti, $jalurDiikuti] = $this->gunungBerjalur('Merbabu');
        [, $jalurLain] = $this->gunungBerjalur('Semeru');

        $user = User::factory()->create();
        $this->ikuti($user, $diikuti);

        $this->tutup($jalurDiikuti, 'Kebakaran lahan.');
        $this->tutup($jalurLain, 'Aktivitas vulkanik.');

        $kabar = $this->kabar($user);

        $this->assertCount(1, $kabar);
        $this->assertSame('Jalur Merbabu', $kabar[0]['judul']);
    }

    /**
     * Kabar adalah pintu yang paling ramai, dan laporan yang belum diperiksa bocor lewat
     * sini akan menghapus seluruh guna moderasinya.
     */
    public function test_reports_awaiting_or_refused_by_moderation_never_appear(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $this->laporan($trail, ModerationStatus::PENDING, 'Belum diperiksa.');
        $this->laporan($trail, ModerationStatus::REJECTED, 'Ditolak.');

        $this->assertCount(0, $this->kabar($user));
        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page->has('komunitas', 0));
    }

    /**
     * Regresi 25 September 2026: kabar mengambil semua jalur di gunung yang diikuti,
     * termasuk draf dan yang diarsipkan, sehingga status dan laporan tentang jalur yang
     * tidak punya halaman publik ikut tampil (R-022). Pembantu test lama bahkan membuat
     * jalur draf, jadi suite ini justru membuktikan kebocorannya.
     */
    public function test_drafts_and_archived_trails_never_reach_the_feed(): void
    {
        [$gunung, $terbit] = $this->gunungBerjalur('Merbabu');
        $draf = Trail::factory()->easy()->for($gunung)->create(['name' => 'Jalur Draf', 'is_published' => false]);
        $arsip = $this->jalurTerbit($gunung, 'Jalur Arsip');
        $arsip->forceFill(['archived_at' => now()])->saveQuietly();
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $this->tutup($draf, 'Draf ditutup.');
        $this->tutup($arsip, 'Arsip ditutup.');
        $this->laporan($draf, ModerationStatus::APPROVED, 'Laporan jalur draf.');
        $this->laporan($arsip, ModerationStatus::APPROVED, 'Laporan jalur arsip.');
        $this->tutup($terbit, 'Longsor di Pos 2.');

        $judul = $this->kabar($user)->pluck('judul')->all();

        $this->assertSame(['Jalur Merbabu'], $judul);
    }

    /**
     * Penutupan diumumkan untuk kawasan, bukan hanya untuk jalur, dan pendaki yang
     * mengikuti Merbabu perlu tahu ketika seluruh Merbabu ditutup.
     */
    public function test_a_closure_announced_for_the_whole_mountain_reaches_the_feed(): void
    {
        [$gunung] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        OfficialStatus::create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Besar TN',
            'effective_at' => now(),
            'reason' => 'Aktivitas vulkanik meningkat.',
        ]);

        $kabar = $this->kabar($user);

        $this->assertCount(1, $kabar);
        $this->assertSame('Merbabu', $kabar[0]['judul']);
    }

    public function test_the_feed_is_ordered_newest_first(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $lama = $this->laporan($trail, ModerationStatus::APPROVED, 'Yang lama.');
        $lama->update(['created_at' => now()->subDays(5)]);

        $this->tutup($trail, 'Yang baru.');

        $this->assertSame(TrailNewsService::STATUS, $this->kabar($user)[0]['jenis']);
    }

    /**
     * Pendaki yang belum mengikuti gunung mana pun tidak diberi layar kosong, melainkan
     * ajakan memilih beserta alasannya.
     */
    public function test_someone_following_nothing_is_invited_to_choose(): void
    {
        $user = User::factory()->create();
        $this->gunungBerjalur('Merbabu');

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/TrailNews')
            ->where('title', 'Kabar Jalur')
            ->where('mengikuti', null)
            ->has('resmi', 0)
            ->has('komunitas', 0));

        $this->assertStringContainsString('Belum ada gunung yang Anda ikuti', $this->sumber());
    }

    /**
     * PRD §92, R-009, R-010: keterangan resmi dan masukan komunitas tidak pernah
     * tertukar. Keduanya tiba di kelompok masing-masing, dan laporan komunitas tidak
     * pernah masuk kelompok resmi.
     */
    public function test_official_and_community_entries_stay_in_separate_groups(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('mengikuti', 'Merbabu')
            ->has('resmi', 1)
            ->where('resmi.0.judul', 'Jalur Merbabu')
            ->where('resmi.0.status', OfficialStatusValue::CLOSED->value)
            ->where('resmi.0.statusLabel', OfficialStatusValue::CLOSED->label())
            ->where('resmi.0.cakupan', StatusScope::TRAIL->label())
            ->has('komunitas', 1)
            ->where('komunitas.0.catatan', 'Jembatan Pos 3 putus.'));

        $sumber = $this->sumber();
        // Pembedanya label berkata-kata, bukan warna saja. Sejak perbaikan P-2 baris
        // resmi memakai LabelSumber (jenis sumber catatan, bukan chip "Resmi" tetap;
        // kepekaannya diuji tersendiri di bawah), sementara laporan komunitas tetap
        // berlatar token community (pasangan yang dihitung ColourContrastTest di kedua
        // tema).
        $this->assertStringContainsString('<LabelSumber jenis={butir.jenisSumber} />', $sumber);
        $this->assertStringContainsString('bg-community-50 px-2 py-0.5 text-xs font-medium text-community-900">Laporan komunitas</span>', $sumber);
        $this->assertStringContainsString('Laporan komunitas tidak mengubah status resmi jalur.', $sumber);
    }

    /**
     * P-2 (R-009, keputusan koordinator nomor 6): jenis sumber baris status mengikuti
     * data_sources, bukan chip "Resmi" tetap untuk setiap catatan di official_statuses
     * (§92).
     */
    public function test_an_official_sourced_entry_carries_the_official_source_type(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        OfficialStatus::create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Besar TN',
            'effective_at' => now(),
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value])->id,
        ]);

        $props = $this->halaman($user)->viewData('page')['props'];

        $this->assertSame('OFFICIAL', $props['resmi'][0]['jenisSumber']);
    }

    /**
     * Kebalikannya: catatan yang disalin admin tidak boleh meminjam label "Resmi" (R-009).
     */
    public function test_an_admin_verified_entry_is_not_labelled_official(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        OfficialStatus::create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Catatan admin',
            'effective_at' => now(),
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::ADMIN_VERIFIED->value])->id,
        ]);

        $props = $this->halaman($user)->viewData('page')['props'];

        $this->assertSame('ADMIN_VERIFIED', $props['resmi'][0]['jenisSumber']);
        $this->assertNotSame('OFFICIAL', $props['resmi'][0]['jenisSumber']);
    }

    /**
     * Setiap butir membawa sumber dan waktunya sendiri, supaya pembaca dapat menilai
     * seberapa jauh ia mempercayainya.
     */
    public function test_every_entry_carries_its_source_and_time(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('resmi.0.sumber', 'Balai Besar TN')
            ->where('resmi.0.waktuIso', fn (string $iso) => $iso !== '')
            ->where('komunitas.0.pelapor', 'Rani')
            ->where('komunitas.0.waktu', fn (string $waktu) => $waktu !== ''));
    }

    /**
     * PRD §59: kesegaran laporan diukur dari tanggal pendakiannya. Laporan tentang
     * pendakian sepuluh hari lalu ditandai mulai lama, walau baru dikirim hari ini.
     */
    public function test_an_aging_report_is_marked_by_its_hike_date(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        $this->laporan($trail, ModerationStatus::APPROVED)->update(['hike_date' => now()->subDays(10)->toDateString()]);

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('komunitas.0.kesegaran', 'Mulai lama')
            ->where('komunitas.0.kesegaranLama', true));
    }

    /**
     * R-013: cuaca selalu membawa sumber, wilayah acuan, dan kesegarannya.
     */
    public function test_current_weather_comes_with_its_source_area_and_freshness(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $trail->forceFill(['weather_adm4_code' => '33.09.04.2001', 'weather_reference_area' => 'Selo, Boyolali'])->saveQuietly();
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        WeatherSnapshot::factory()->create([
            'adm4_code' => '33.09.04.2001',
            'reference_area' => 'Selo, Boyolali',
            'forecast_at' => now()->addHour(),
            'weather_description' => 'Cerah Berawan',
            'temperature_c' => 24,
            'wind_speed_kmh' => 12,
            'fetched_at' => now()->subHour(),
        ]);

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('cuaca', 1)
            ->where('cuaca.0.gunung', 'Merbabu')
            ->where('cuaca.0.wilayah.0.area', 'Selo, Boyolali')
            ->where('cuaca.0.wilayah.0.sumber', 'BMKG')
            ->where('cuaca.0.wilayah.0.ringkasan', 'Cerah Berawan · 24 °C · angin 12 km/jam')
            ->where('cuaca.0.wilayah.0.kesegaran', 'Terbaru')
            ->where('cuaca.0.wilayah.0.pesan', null));
    }

    /**
     * R-013: snapshot yang sudah lama tidak dibacakan sebagai cuaca saat ini. Yang
     * tampil adalah kapan data terakhir tersedia.
     */
    public function test_stale_weather_is_never_presented_as_current(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);

        WeatherSnapshot::factory()->create([
            'adm4_code' => $trail->weather_adm4_code,
            'forecast_at' => now(),
            'fetched_at' => now()->subDays(3),
        ]);

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cuaca.0.wilayah.0.ringkasan', null)
            ->where('cuaca.0.wilayah.0.kesegaran', 'Sudah lama')
            ->where('cuaca.0.wilayah.0.pesan', fn (string $pesan) => str_starts_with($pesan, 'Data cuaca belum berhasil diperbarui. Data terakhir tersedia pada ')));
    }

    /**
     * Tanpa snapshot sama sekali, kalimatnya mengaku tidak tersedia; tidak ada cuaca yang
     * dikarang untuk mengisi kekosongan.
     */
    public function test_missing_weather_is_admitted_not_invented(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        [$tanpaWilayah, $jalurTanpaWilayah] = $this->gunungBerjalur('Sumbing');
        $jalurTanpaWilayah->forceFill(['weather_adm4_code' => null])->saveQuietly();

        $user = User::factory()->create();
        $this->ikuti($user, $gunung);
        $this->ikuti($user, $tanpaWilayah);

        $this->halaman($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cuaca.0.gunung', 'Merbabu')
            ->where('cuaca.0.wilayah.0.ringkasan', null)
            ->where('cuaca.0.wilayah.0.pesan', 'Data cuaca tidak tersedia.')
            ->where('cuaca.1.gunung', 'Sumbing')
            ->has('cuaca.1.wilayah', 0)
            ->where('cuaca.1.pesan', 'Data cuaca tidak tersedia.'));
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function kabarBeranda(User $user): ?array
    {
        return $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['kabar'];
    }

    /**
     * Ringkasan Beranda (W5) memakai kueri per jenis: laporan komunitas terbaru tetap ada
     * walau status resmi yang lebih baru lebih banyak dari batasnya.
     */
    public function test_the_latest_per_kind_keeps_a_report_that_newer_statuses_would_push_out(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);
        $this->laporan($trail, ModerationStatus::APPROVED);
        $this->travel(1)->hours();

        foreach (['Longsor di Pos 2.', 'Perbaikan jembatan.', 'Kebakaran lahan.'] as $alasan) {
            $this->tutup($trail, $alasan);
            $this->travel(1)->minutes();
        }

        $terbaru = app(TrailNewsService::class)->terbaru($user, 2, 1);

        $this->assertSame(['Kebakaran lahan.', 'Perbaikan jembatan.'], $terbaru['resmi']->pluck('alasan')->all());
        $this->assertCount(1, $terbaru['laporan']);
        $this->assertSame([TrailNewsService::STATUS, TrailNewsService::STATUS], app(TrailNewsService::class)->forUser($user, 2)->pluck('jenis')->all());
    }

    /**
     * Beranda meringkas kabar gunung yang diikuti dalam dua kelompok terpisah (R-010), dengan
     * kalimat R-012 di samping status resmi dan tautan ke halaman Kabar. Laporan yang belum
     * lolos moderasi tidak ikut.
     */
    public function test_the_home_summarises_followed_mountains_with_official_and_community_apart(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = $this->pendakiBerprofil();
        $this->ikuti($user, $gunung);
        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);
        $this->laporan($trail, ModerationStatus::PENDING, 'Belum diperiksa.');

        $kabar = $this->kabarBeranda($user);

        $this->assertSame(['Kabar Merbabu yang Anda ikuti', true, null, null], [$kabar['judul'], $kabar['diikuti'], $kabar['mengikuti'], $kabar['ikuti']]);
        $this->assertSame(['Jalur Merbabu', 'Tutup', 'Kebakaran lahan.'], [$kabar['resmi'][0]['judul'], $kabar['resmi'][0]['statusLabel'], $kabar['resmi'][0]['alasan']]);
        $this->assertSame(['Jalur Merbabu', 'Jembatan Pos 3 putus.'], [$kabar['komunitas'][0]['jalur'], $kabar['komunitas'][0]['catatan']]);
        $this->assertCount(1, $kabar['komunitas']);

        $sumber = File::get(resource_path('js/components/beranda/KabarDiikuti.tsx'));
        $this->assertStringContainsString('<KondisiPendaki laporan={kabar.komunitas} />', $sumber);
        $this->assertStringContainsString('href={news.url()}', $sumber);
    }

    /**
     * Ruling W5-FI (M7 tinjauan akhir W5, butir inventaris 3): Beranda meringkas paling banyak dua
     * perubahan status resmi dan satu laporan komunitas, yang terbaru lebih dulu
     * (KabarJalur::RINGKAS_RESMI dan RINGKAS_LAPORAN), walau datanya lebih banyak; sisanya di
     * halaman Kabar.
     */
    public function test_the_home_keeps_the_two_newest_official_changes_and_the_newest_report(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = $this->pendakiBerprofil();
        $this->ikuti($user, $gunung);

        foreach (['Longsor di Pos 2.', 'Perbaikan jembatan.', 'Kebakaran lahan.'] as $alasan) {
            $this->tutup($trail, $alasan);
            $this->travel(1)->minutes();
        }

        foreach (['Licin setelah hujan.', 'Jembatan Pos 3 putus.'] as $catatan) {
            $this->laporan($trail, ModerationStatus::APPROVED, $catatan);
            $this->travel(1)->minutes();
        }

        $kabar = $this->kabarBeranda($user);

        $this->assertSame(['Kebakaran lahan.', 'Perbaikan jembatan.'], array_column($kabar['resmi'], 'alasan'));
        $this->assertSame(['Jembatan Pos 3 putus.'], array_column($kabar['komunitas'], 'catatan'));
    }

    public function test_the_home_names_several_followed_mountains(): void
    {
        [$merbabu] = $this->gunungBerjalur('Merbabu');
        [$sumbing] = $this->gunungBerjalur('Sumbing');
        $user = $this->pendakiBerprofil();
        $this->ikuti($user, $merbabu);
        $this->ikuti($user, $sumbing);

        $kabar = $this->kabarBeranda($user);

        $this->assertSame(['Kabar gunung yang Anda ikuti', 'Merbabu dan Sumbing'], [$kabar['judul'], $kabar['mengikuti']]);
        $this->assertSame([[], []], [$kabar['resmi'], $kabar['komunitas']]);
    }

    /**
     * Trip mendatang menuju gunung yang belum diikuti: Beranda menawarkan mengikutinya, dan
     * sesudah diikuti tawarannya hilang dan kabarnya masuk.
     */
    public function test_the_home_offers_to_follow_the_trip_mountain(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = $this->pendakiBerprofil();
        TripPlan::factory()->for($user)->for($trail)->create(['planned_date' => now()->addDays(10)->toDateString(), 'status' => TripStatus::PLANNED->value]);

        $kabar = $this->kabarBeranda($user);

        $this->assertFalse($kabar['diikuti']);
        $this->assertSame(['gunung' => 'Merbabu', 'href' => route('mountains.follow', $gunung)], $kabar['ikuti']);

        $this->actingAs($user)->from(route('dashboard'))->post($kabar['ikuti']['href'], ['ya' => true])->assertRedirect(route('dashboard'));

        $sesudah = $this->kabarBeranda($user);
        $this->assertSame([true, null], [$sesudah['diikuti'], $sesudah['ikuti']]);
    }

    /**
     * Ruling W5-FH (M6 tinjauan akhir W5): tombol "Ikuti {gunung}" hilang begitu gunungnya
     * diikuti. Sesudah berhasil, fokus pindah ke judul bagian kabar (tabIndex -1), tidak jatuh ke
     * body (DESIGN.md), dan hasilnya diumumkan di wilayah status yang selalu terpasang, karena
     * MountainFollowController tidak mengirim kabar apa pun (WCAG 4.1.3).
     */
    public function test_following_from_the_home_moves_focus_to_the_news_heading_and_announces_it(): void
    {
        $sumber = File::get(resource_path('js/components/beranda/KabarDiikuti.tsx'));

        $this->assertStringContainsString('<h2 id="kabar-diikuti" ref={judul} tabIndex={-1}', $sumber);
        $this->assertMatchesRegularExpression('/onSuccess: \(\) => \{\s*setKabarIkuti\(`Anda mengikuti \$\{gunung\}\.`\);\s*judul\.current\?\.focus\(\);\s*\}/', $sumber);
        $this->assertStringContainsString('onClick={() => ikuti(tujuan.gunung, tujuan.href)}', $sumber);
        // Wilayah status di luar cabang mana pun, jadi sudah terpasang sebelum kabarnya datang.
        $this->assertMatchesRegularExpression('/<p role="status" className="sr-only">\s*\{kabarIkuti\}\s*<\/p>\s*<\/section>/', $sumber);
    }

    /**
     * Sebelum profil terisi Beranda punya satu fokus, mengisi profil; selama pendakian berjalan,
     * mode lapangan. Kabar tidak dikirim pada kedua fase itu.
     */
    public function test_the_home_leaves_news_out_before_a_profile_and_during_a_hike(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $tanpaProfil = User::factory()->create();
        $this->ikuti($tanpaProfil, $gunung);

        $this->assertNull($this->kabarBeranda($tanpaProfil));

        $mendaki = $this->pendakiBerprofil();
        $this->ikuti($mendaki, $gunung);
        TripPlan::factory()->for($mendaki)->for($trail)->create(['planned_date' => now()->toDateString(), 'status' => TripStatus::IN_PROGRESS->value]);

        $this->assertNull($this->kabarBeranda($mendaki));
    }
}
