<?php

namespace Tests\Feature;

use App\Enums\ConditionTag;
use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\RouteFitLabel;
use App\Enums\StatusScope;
use App\Enums\TripType;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Sejak cek terakhir" (spec v3 bagian 6.11, prototype 10, W4 Tugas 6): halaman kesiapan
 * membandingkan penilaian sekarang dengan snapshot readiness_checks terakhir yang disimpan
 * pendaki, lalu menyebut apa yang berubah: status resmi, laporan komunitas baru, butir
 * persiapan yang dikonfirmasi, dan label kecocokan.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB.
 */
class ReadinessSinceLastCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
        ]);

        foreach (['Headlamp dan baterai cadangan', 'Jas hujan', 'Kontak darurat'] as $label) {
            TripPreparationItem::create([
                'trip_plan_id' => $trip->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $label,
                'is_critical' => true,
                'status' => PreparationStatus::NOT_CONFIRMED->value,
            ]);
        }

        return $trip->fresh();
    }

    /**
     * Fixture CAMPURAN (m2, putaran perbaikan 3, probe R5-a/b): 2 butir KRITIS + 1 butir
     * NON-KRITIS, beda dari trip() yang seluruh 3 butirnya kritis. Dibutuhkan karena koreksi
     * atas klaim putaran 2: test m2 yang mengaku menguji butir non-kritis sebelumnya memakai
     * trip(), sehingga diam-diam menguji butir KRITIS (lihat
     * test_a_critical_item_returning_from_not_applicable_is_named_as_changed di bawah, nama
     * baru untuk test lama itu). Dua butir kritis di sini dibiarkan TIDAK TERSENTUH di
     * sepanjang test R5-a/R5-b yang memakainya supaya critical_outstanding (himpunan label
     * kritis) identik sebelum/sesudah -- perubahan yang terdeteksi harus murni berasal dari
     * jalur notConfirmedDelta/notApplicableDelta yang baru, bukan dari himpunan kritis.
     */
    private function tripCampuran(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
        ]);

        foreach (['Headlamp dan baterai cadangan', 'Jas hujan'] as $label) {
            TripPreparationItem::create([
                'trip_plan_id' => $trip->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $label,
                'is_critical' => true,
                'status' => PreparationStatus::NOT_CONFIRMED->value,
            ]);
        }

        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => 'Trekking pole',
            'is_critical' => false,
            'status' => PreparationStatus::NOT_CONFIRMED->value,
        ]);

        return $trip->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sejak(TripPlan $trip): ?array
    {
        return $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk()->viewData('page')['props']['sejakTerakhir'];
    }

    public function test_there_is_nothing_to_compare_before_the_first_saved_check(): void
    {
        $this->assertNull($this->sejak($this->trip()));
    }

    /**
     * Prototype 10: "= Status resmi tidak berubah. + 1 laporan komunitas baru. + 2 butir
     * persiapan dikonfirmasi. = Label kecocokan tetap ...". Laporan yang dihitung hanya yang
     * lolos moderasi dan masuk sesudah cek terakhir.
     */
    public function test_the_comparison_names_what_changed_since_the_saved_check(): void
    {
        $trip = $this->trip();
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'created_at' => now()->subDay()]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip))->assertRedirect(route('trips.readiness', $trip));

        $this->travel(2)->hours();
        $trip->preparationItems()->limit(2)->get()->each->update(['status' => PreparationStatus::CONFIRMED->value]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-09-24', 'condition_tags' => [ConditionTag::SLIPPERY->value]]);
        TrailConditionReport::factory()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-09-24']);

        $sejak = $this->sejak($trip);

        $this->assertSame('25 Sep', $sejak['tanggal']);
        $this->assertSame(['sama', 'bertambah', 'bertambah', 'sama'], array_column($sejak['butir'], 'jenis'));
        $this->assertSame('Status resmi tidak berubah.', $sejak['butir'][0]['teks']);
        $this->assertSame('1 laporan komunitas baru.', $sejak['butir'][1]['teks']);
        $this->assertSame('2 butir persiapan dikonfirmasi.', $sejak['butir'][2]['teks']);
        $this->assertStringStartsWith('Label kecocokan tetap ', $sejak['butir'][3]['teks']);
    }

    /**
     * Penutupan sesudah cek terakhir disebut sebagai perubahan, dengan kata status lama dan
     * barunya; tidak ada yang berubah disebut tidak berubah, bukan didiamkan.
     */
    public function test_a_status_change_since_the_saved_check_is_named(): void
    {
        $trip = $this->trip();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->days();
        OfficialStatus::create([
            'statusable_type' => $trip->trail->getMorphClass(),
            'statusable_id' => $trip->trail_id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Taman Nasional',
            'effective_at' => now(),
        ]);

        $sejak = $this->sejak($trip);

        $this->assertSame(['jenis' => 'berubah', 'teks' => 'Status resmi berubah dari Buka menjadi Tutup.'], $sejak['butir'][0]);
        $this->assertSame(['jenis' => 'sama', 'teks' => 'Persiapan tidak berubah.'], $sejak['butir'][2]);
    }

    /**
     * I5(b), M4 (task-6-review.md bagian 7): latestCheck() wajib mengurutkan ke baris
     * TERBARU (`latest('id')`), bukan tertua. Mutasi yang menukarnya ke oldest('id') lolos
     * dari seluruh suite Unit+Feature karena test lain hanya menyimpan satu baris; di sini
     * ada DUA cek dengan tanggal dan hitungan persiapan yang berbeda, jadi arah yang salah
     * akan menghasilkan tanggal dan hitungan yang berbeda pula.
     */
    public function test_the_comparison_uses_the_more_recent_saved_check_not_the_oldest(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->days();
        $trip->preparationItems()->orderBy('id')->limit(1)->get()->each->update(['status' => PreparationStatus::CONFIRMED->value]);
        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->hours();
        $trip->preparationItems()->orderBy('id')->limit(2)->get()->each->update(['status' => PreparationStatus::CONFIRMED->value]);

        $sejak = $this->sejak($trip);

        $this->assertSame('26 Sep', $sejak['tanggal']);
        $this->assertSame('1 butir persiapan dikonfirmasi.', $sejak['butir'][2]['teks']);
    }

    /**
     * m2 (P4): dua butir kritis bertukar status (satu dikonfirmasi, satu dibatalkan) saling
     * meniadakan di selisih bersih confirmed, tetapi keduanya benar-benar berubah dan tidak
     * boleh terbaca "Persiapan tidak berubah."
     */
    public function test_swapping_two_critical_items_is_named_as_changed_not_unchanged(): void
    {
        $trip = $this->trip();
        $items = $trip->preparationItems()->orderBy('id')->get();
        $items[0]->update(['status' => PreparationStatus::CONFIRMED->value]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->hours();
        $items[0]->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);
        $items[1]->update(['status' => PreparationStatus::CONFIRMED->value]);

        $sejak = $this->sejak($trip);

        $this->assertSame(['jenis' => 'berubah', 'teks' => 'Status 2 butir persiapan berubah.'], $sejak['butir'][2]);
    }

    /**
     * m2 (P5): butir kritis yang sudah dikonfirmasi lalu ditandai Tidak berlaku kehilangan
     * statusnya yang benar. "Kembali belum dikonfirmasi" menyiratkan ia masih perlu
     * dikonfirmasi, padahal ia justru sudah tidak berlaku lagi.
     */
    public function test_marking_a_confirmed_item_not_applicable_is_not_read_as_unconfirmed(): void
    {
        $trip = $this->trip();
        $item = $trip->preparationItems()->orderBy('id')->first();
        $item->update(['status' => PreparationStatus::CONFIRMED->value]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->hours();
        $item->update(['status' => PreparationStatus::NOT_APPLICABLE->value]);

        $sejak = $this->sejak($trip);

        $this->assertSame(['jenis' => 'berubah', 'teks' => '1 butir ditandai tidak berlaku.'], $sejak['butir'][2]);
    }

    /**
     * m2 (P6, putaran perbaikan 3). KOREKSI NAMA: sebelum putaran ini test ini bernama
     * test_a_non_critical_item_returning_from_not_applicable_is_named_as_changed dan
     * diklaim (task-6-report.md putaran 2) menguji butir NON-KRITIS, padahal ->first() di
     * sini membaca dari trip() yang SELURUH 3 butirnya kritis (lihat helper trip() di atas) --
     * jadi yang benar-benar diuji adalah butir kritis. Kode test tidak diubah (skenarionya
     * tetap valid untuk butir kritis), hanya namanya diperbaiki. Skenario non-kritis yang
     * genuinely terpisah ada di test_a_non_critical_item_returning_from_not_applicable_is_named_as_changed
     * di bawah, memakai tripCampuran().
     */
    public function test_a_critical_item_returning_from_not_applicable_is_named_as_changed(): void
    {
        $trip = $this->trip();
        $item = $trip->preparationItems()->orderBy('id')->first();
        $item->update(['status' => PreparationStatus::NOT_APPLICABLE->value]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->hours();
        $item->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $sejak = $this->sejak($trip);

        $this->assertSame(['jenis' => 'berubah', 'teks' => 'Status 1 butir persiapan berubah.'], $sejak['butir'][2]);
    }

    /**
     * m2 (probe R5-a, putaran perbaikan 3, GENUINELY non-kritis). Dibuat ulang karena test
     * putaran 2 dengan nama ini ternyata memakai fixture yang semua butirnya kritis (lihat
     * koreksi pada test_a_critical_item_returning_from_not_applicable_is_named_as_changed di
     * atas). Memakai tripCampuran(): dua butir kritisnya TIDAK TERSENTUH sepanjang test, jadi
     * critical_outstanding identik sebelum/sesudah dan satu-satunya jalur yang bisa menangkap
     * perubahan butir non-kritis ini adalah notConfirmedDelta di sejakPersiapan(): bila syarat
     * `|| $notConfirmedDelta !== 0` dihapus, test ini jatuh ke cabang terakhir dan salah membaca
     * "Persiapan tidak berubah." Mutasi N15 (cabang himpunan kritis dibuang) TIDAK menjatuhkan
     * test ini, karena himpunan kritisnya memang tidak berubah di sini; N15 dibunuh test butir
     * kritis di atas (koreksi NIT-C W4-6l).
     */
    public function test_a_non_critical_item_returning_from_not_applicable_is_named_as_changed(): void
    {
        $trip = $this->tripCampuran();
        $nonKritis = $trip->preparationItems()->where('is_critical', false)->first();
        $nonKritis->update(['status' => PreparationStatus::NOT_APPLICABLE->value]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->hours();
        $nonKritis->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $sejak = $this->sejak($trip);

        $this->assertSame(['jenis' => 'berubah', 'teks' => 'Status 1 butir persiapan berubah.'], $sejak['butir'][2]);
    }

    /**
     * m2 (probe R5-b, putaran perbaikan 3): satu butir KRITIS dikonfirmasi bersamaan dengan
     * satu butir NON-KRITIS ditandai tidak berlaku. Keduanya harus disebut sekaligus -- cabang
     * gabungan ($confirmedDelta > 0 && $notApplicableDelta > 0) di sejakPersiapan() diperiksa
     * lebih dulu, sebelum cabang confirmedDelta atau notApplicableDelta tunggal yang hanya
     * menyebut satu sisi dan akan diam-diam membuang sisi lainnya.
     */
    public function test_confirming_one_item_while_another_becomes_not_applicable_names_both(): void
    {
        $trip = $this->tripCampuran();

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->hours();
        $trip->preparationItems()->where('is_critical', true)->orderBy('id')->first()->update(['status' => PreparationStatus::CONFIRMED->value]);
        $trip->preparationItems()->where('is_critical', false)->first()->update(['status' => PreparationStatus::NOT_APPLICABLE->value]);

        $sejak = $this->sejak($trip);

        $this->assertSame(
            ['jenis' => 'berubah', 'teks' => '1 butir persiapan dikonfirmasi, 1 ditandai tidak berlaku.'],
            $sejak['butir'][2]
        );
    }

    /**
     * m7 (M10): baris label kecocokan menyebut KEDUA labelnya ketika berubah, bukan hanya
     * "tetap". Label lama ditulis langsung ke baris tersimpan (dipilih berlawanan dengan
     * label sekarang yang benar-benar terhitung), supaya perbedaannya pasti terjadi tanpa
     * bergantung pada ambang skor CompatibilityScorer yang sebenarnya.
     */
    public function test_a_fit_label_change_is_named_with_both_labels(): void
    {
        $trip = $this->trip();
        $kiniLabel = app(ReadinessService::class)->compute($trip)->routeFitSnapshot['label'];
        $laluLabel = $kiniLabel === RouteFitLabel::KURANG_COCOK->value ? RouteFitLabel::COCOK->value : RouteFitLabel::KURANG_COCOK->value;

        $check = app(ReadinessService::class)->record($trip);
        $check->forceFill(['route_fit_snapshot' => array_merge($check->route_fit_snapshot, ['label' => $laluLabel])])->save();

        $sejak = $this->sejak($trip);

        $kiniLabelText = RouteFitLabel::tryFrom((string) $kiniLabel)?->label() ?? RouteFitLabel::UNRATED_TEXT;

        $this->assertSame('berubah', $sejak['butir'][3]['jenis']);
        $this->assertSame(
            'Label kecocokan berubah dari '.RouteFitLabel::from($laluLabel)->label().' menjadi '.$kiniLabelText.'.',
            $sejak['butir'][3]['teks']
        );
    }

    /**
     * m7 (M8): kondisi.laporan membawa ringkasan tanda, nama segmen, kapan, dan
     * kesegarannya -- bukan hanya menyatakan ada laporan.
     */
    public function test_a_report_appears_with_its_summary_segment_and_freshness(): void
    {
        $trip = $this->trip();
        $segmen = TrailSegment::factory()->create(['trail_id' => $trip->trail_id, 'name' => 'Segmen Puncak']);
        TrailConditionReport::factory()->approved()->create([
            'trail_id' => $trip->trail_id,
            'trail_segment_id' => $segmen->id,
            'hike_date' => now()->subDay()->toDateString(),
            'condition_tags' => [ConditionTag::SLIPPERY->value],
        ]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];
        $laporan = $props['kondisi']['laporan'];

        $this->assertNotNull($laporan);
        $this->assertSame('Licin', $laporan['ringkas']);
        $this->assertSame('Segmen Puncak', $laporan['segmen']);
        $this->assertSame('pendakian kemarin', $laporan['kapan']);
        $this->assertNotNull($laporan['kesegaran']);
    }

    /**
     * m7 (M19): tanggal "Sejak cek terakhir" dibaca dalam zona gunung, bukan UTC. Dibekukan
     * dekat tengah malam UTC (24 Sep 18.00 UTC = 25 Sep 01.00 WIB) supaya tanggal UTC dan
     * WIB benar-benar berbeda hari; memformat computed_at tanpa mengonversi zona akan
     * terbaca sehari lebih awal.
     */
    public function test_the_since_last_check_date_reads_in_the_mountain_zone_near_utc_midnight(): void
    {
        $this->travelTo(Carbon::parse('2026-09-24 18:00:00', 'UTC'));
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(2)->hours();
        $sejak = $this->sejak($trip);

        $this->assertSame('25 Sep', $sejak['tanggal']);
    }
}
