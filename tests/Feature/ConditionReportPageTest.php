<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ModerationStatus;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Formulir laporan kondisi (FR-13) setelah pindah ke React: formulirnya milik klien,
 * validasi, batas laju, penautan ke riwayat, dan pembersihan foto tetap di server.
 */
class ConditionReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.report_photos_disk'));
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function kirim(User $user, Trail $trail, array $ubah = []): TestResponse
    {
        return $this->actingAs($user)->post(route('reports.store'), array_merge([
            'trail_id' => $trail->id,
            'hike_date' => now()->subDay()->toDateString(),
            'condition_tags' => ['MUDDY'],
            'note' => 'Licin setelah hujan.',
        ], $ubah));
    }

    public function test_the_form_offers_published_trails_and_prefills_from_the_link(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Merbabu']);
        $terbit = Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur Selo']);
        Trail::factory()->unpublished()->create(['name' => 'Jalur Draft']);
        TrailSegment::factory()->create(['trail_id' => $terbit->id, 'sequence' => 1, 'name' => 'Basecamp ke Pos 1']);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.create', ['trail' => $terbit->id, 'trip' => 7]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Community/ConditionReport')
                ->where('title', 'Laporan Kondisi Jalur')
                ->has('trails', 1)
                ->where('trails.0.label', 'Jalur Selo - Merbabu')
                ->where('awal.trail_id', $terbit->id)
                ->where('awal.trip_plan_id', 7)
                ->has('segments', 1)
                ->where('segments.0.label', '1. Basecamp ke Pos 1')
                ->has('tags', 11));
    }

    public function test_a_guest_cannot_submit_a_report(): void
    {
        $this->post(route('reports.store'), ['trail_id' => Trail::factory()->create()->id])->assertRedirect(route('login'));

        $this->assertSame(0, TrailConditionReport::count());
    }

    public function test_an_empty_report_names_every_missing_field(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), [])
            ->assertSessionHasErrors([
                'trail_id' => 'Jalur wajib diisi.',
                'hike_date' => 'Tanggal pendakian wajib diisi.',
                'condition_tags' => 'Kondisi jalur wajib diisi.',
            ]);

        $this->assertSame(0, TrailConditionReport::count());
    }

    public function test_a_segment_from_another_trail_is_refused(): void
    {
        $trail = Trail::factory()->create();
        $segmenLain = TrailSegment::factory()->create(['trail_id' => Trail::factory()->create()->id]);

        $this->kirim(User::factory()->create(), $trail, ['trail_segment_id' => $segmenLain->id])
            ->assertSessionHasErrors(['trail_segment_id' => 'Segmen jalur yang dipilih tidak sah.']);
    }

    public function test_an_unknown_condition_is_refused(): void
    {
        $this->kirim(User::factory()->create(), Trail::factory()->create(), ['condition_tags' => ['BANJIR_BANDANG']])
            ->assertSessionHasErrors('condition_tags.0');

        $this->assertSame(0, TrailConditionReport::count());
    }

    /**
     * Laporan masuk moderasi, pengirim kembali ke riwayatnya dan diberi tahu. Laporan
     * komunitas tidak pernah menjadi status resmi (R-010), jadi belum tampil untuk
     * siapa pun sampai moderator menyetujuinya.
     */
    public function test_a_valid_report_waits_for_moderation_and_returns_to_history(): void
    {
        $user = User::factory()->create();

        $this->kirim($user, Trail::factory()->create())
            ->assertRedirect(route('history'))
            ->assertSessionHas('status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.');

        $laporan = TrailConditionReport::sole();
        $this->assertSame(ModerationStatus::PENDING, $laporan->moderation_status);
        $this->assertSame($user->id, $laporan->user_id);
        $this->assertSame('Licin setelah hujan.', $laporan->note);
    }

    /**
     * IDOR: id trip dikirim klien, jadi penautannya disaring pemiliknya. Trip orang lain
     * tidak pernah tertaut ke laporan yang bukan miliknya.
     */
    public function test_a_report_links_only_to_the_senders_own_history(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $trail = Trail::factory()->create();
        $riwayatSaya = $this->riwayat($saya, $trail);
        $riwayatOrangLain = $this->riwayat($orangLain, $trail);

        $this->kirim($saya, $trail, ['trip_plan_id' => $riwayatOrangLain->trip_plan_id]);
        $this->assertNull($riwayatOrangLain->fresh()->trail_condition_report_id);

        $this->kirim($saya, $trail, ['trip_plan_id' => $riwayatSaya->trip_plan_id]);
        $this->assertNotNull($riwayatSaya->fresh()->trail_condition_report_id);
    }

    /**
     * Ruling W5-FC (IDOR): tanggal pendakian bawaan hanya dibaca dari riwayat MILIK pengirim. Id
     * trip pendaki lain di tautan tidak mengubah bawaannya, dan tanggal pendakian pendaki lain
     * tidak ikut terkirim ke halaman.
     */
    public function test_another_hikers_trip_never_sets_the_default_hike_date(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
        $trail = Trail::factory()->published()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->create();
        $riwayatOrangLain = $this->riwayat(User::factory()->create(), $trail);
        $riwayatOrangLain->update(['completed_at' => Carbon::parse('2026-09-20 02:00:00', 'UTC')]);

        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('reports.create', ['trail' => $trail->id, 'trip' => $riwayatOrangLain->trip_plan_id]))
            ->assertOk();

        $halaman->assertInertia(fn (AssertableInertia $page) => $page->where('awal.hike_date', '2026-09-25'));
        $this->assertStringNotContainsString('2026-09-20', json_encode($halaman->viewData('page')['props']));
    }

    /**
     * Ruling W5-FR (N4 tinjauan ulang): tanggal selesai riwayat dijepit ke tanggal maksimum
     * formulir, batas yang sama dengan validasi StoreConditionReportRequest, jadi bawaannya tidak
     * pernah tanggal yang akan ditolak formulirnya sendiri. Riwayat milik sendiri yang
     * completed_at-nya sesudah tanggal WIT hari ini (data menyimpang) memberi tanggal maksimum.
     */
    public function test_a_hike_date_past_the_forms_maximum_is_clamped_to_it(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
        $user = User::factory()->create();
        $trail = Trail::factory()->published()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->create();
        $riwayat = $this->riwayat($user, $trail);
        $riwayat->update(['completed_at' => Carbon::parse('2026-09-27 02:00:00', 'UTC')]);

        $this->actingAs($user)
            ->get(route('reports.create', ['trail' => $trail->id, 'trip' => $riwayat->trip_plan_id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('tanggalMaksimum', '2026-09-25')
                ->where('awal.hike_date', '2026-09-25'));
    }

    /**
     * Ruling W5-FC: tanpa riwayat milik sendiri, bawaannya tanggal hari ini di zona gunung jalur
     * itu, bukan tanggal UTC. Jumat 25 September 01.30 WIB masih Kamis 24 September di UTC.
     */
    public function test_the_default_hike_date_is_today_in_the_mountains_zone_not_utc(): void
    {
        $this->travelTo(Carbon::parse('2026-09-24 18:30:00', 'UTC'));
        $trail = Trail::factory()->published()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->create();

        $this->actingAs(User::factory()->create())
            ->get(route('reports.create', ['trail' => $trail->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('awal.hike_date', '2026-09-25')
                ->where('tanggalMaksimum', '2026-09-25'));
    }

    /**
     * Nama berkas dari ponsel tidak pernah masuk path penyimpanan, dan fotonya melewati
     * pembersih EXIF sebelum tersimpan (PRD §82).
     */
    public function test_a_photo_is_stored_under_a_random_name(): void
    {
        $this->kirim(User::factory()->create(), Trail::factory()->create(), [
            'photo' => UploadedFile::fake()->image('IMG_2026_rumah_saya.jpg', 640, 480),
        ])->assertRedirect(route('history'));

        $path = TrailConditionReport::sole()->photo_path;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('condition-reports/', $path);
        $this->assertStringNotContainsString('rumah_saya', $path);
        Storage::disk(config('filesystems.report_photos_disk'))->assertExists($path);
    }

    /**
     * Regresi: foto yang tidak dapat dibersihkan dibuang dan laporannya tetap tersimpan.
     * Versi Livewire menulis kabar itu ke 'status' lalu menimpanya dengan kabar sukses,
     * sehingga pengirim tidak pernah tahu fotonya tidak ikut.
     */
    public function test_a_photo_that_cannot_be_cleaned_is_dropped_and_the_sender_is_told(): void
    {
        $user = User::factory()->create();
        $rusak = UploadedFile::fake()->createWithContent('rusak.png', "\x89PNG\r\n\x1a\n".str_repeat("\0", 64));

        $this->kirim($user, Trail::factory()->create(), ['photo' => $rusak])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('history'))
            ->assertSessionHas('foto-tidak-disertakan', 'Foto tidak dapat diproses dan tidak disertakan. Laporan tetap tersimpan.');

        $this->assertNull(TrailConditionReport::sole()->photo_path);
        $this->assertSame([], Storage::disk(config('filesystems.report_photos_disk'))->allFiles());

        $this->actingAs($user)->get(route('history'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('peringatanFoto', 'Foto tidak dapat diproses dan tidak disertakan. Laporan tetap tersimpan.')
            ->where('flash.status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.'));
    }

    /**
     * Formulir yang dirender klien tetap berlabel dan menyebut bahwa laporan komunitas
     * tidak mengubah status resmi.
     */
    public function test_the_form_is_labelled_and_states_what_a_report_is_not(): void
    {
        $sumber = File::get(resource_path('js/pages/Community/ConditionReport.tsx'));

        $this->assertSame(1, substr_count($sumber, '<PageHeader'));
        $this->assertStringContainsString('Laporan komunitas tidak mengubah status resmi jalur.', $sumber);

        foreach (['trail_id', 'trail_segment_id', 'note', 'photo'] as $id) {
            $this->assertStringContainsString('htmlFor="'.$id.'"', $sumber, "Kontrol #{$id} tanpa label.");
        }

        $this->assertStringContainsString('forceFormData: true', $sumber);
    }

    private function riwayat(User $user, Trail $trail): HikingHistory
    {
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 80,
            'completed_at' => now()->subDay(),
        ]);
    }
}
