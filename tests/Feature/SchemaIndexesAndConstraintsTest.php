<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Penjaga performa skema, indeks kunci asing, constraint domain non-negatif,
 * pencegahan lazy loading N+1, dan batas kandidat mesin kecocokan (S2.1: GAP-07 & GAP-08).
 */
class SchemaIndexesAndConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_foreign_key_has_a_covering_leading_index(): void
    {
        $tanpaIndeks = [];

        foreach (Schema::getTableListing(schemaQualified: false) as $tabel) {
            if (in_array($tabel, ['migrations', 'sqlite_sequence'], true)) {
                continue;
            }

            $indeks = Schema::getIndexes($tabel);
            $kolomAwalTerindeks = array_values(array_filter(array_map(
                static fn (array $idx): ?string => $idx['columns'][0] ?? null,
                $indeks,
            )));

            foreach (Schema::getForeignKeys($tabel) as $fk) {
                $kolomFk = $fk['columns'][0] ?? null;

                if ($kolomFk !== null && ! in_array($kolomFk, $kolomAwalTerindeks, true)) {
                    $tanpaIndeks[] = "{$tabel}.{$kolomFk}";
                }
            }
        }

        sort($tanpaIndeks);

        $this->assertSame([], $tanpaIndeks, 'Kolom foreign key tanpa indeks penutup: '.implode(', ', $tanpaIndeks));
    }

    public function test_trails_has_composite_filter_indexes_and_pgsql_trigram_search_indexes(): void
    {
        $namaIndeks = array_column(Schema::getIndexes('trails'), 'name');

        $this->assertContains('trails_published_technical_camping_idx', $namaIndeks);
        $this->assertContains('trails_published_gain_duration_idx', $namaIndeks);

        $berkasMigrasi = base_path('database/migrations/2026_10_15_000200_add_search_filter_and_fk_indexes.php');
        $this->assertFileExists($berkasMigrasi);

        $isi = File::get($berkasMigrasi);
        $this->assertStringContainsString('CREATE EXTENSION IF NOT EXISTS pg_trgm', $isi);
        $this->assertStringContainsString('gin_trgm_ops', $isi);
        $this->assertStringContainsString('trails_name_trgm_idx', $isi);
        $this->assertStringContainsString('mountains_name_trgm_idx', $isi);
    }

    public function test_trip_preparation_items_enforces_partial_unique_per_trip_and_template_item(): void
    {
        $trip = TripPlan::factory()->create();
        $templat = PreparationTemplate::create([
            'name' => 'Daftar Persiapan Standar',
            'is_default' => true,
        ]);
        $butir = PreparationItem::create([
            'preparation_template_id' => $templat->id,
            'category' => PreparationCategory::EQUIPMENT,
            'label' => 'Jas hujan',
            'is_critical' => true,
            'sort_order' => 1,
        ]);

        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'preparation_item_id' => $butir->id,
            'category' => PreparationCategory::EQUIPMENT,
            'label' => 'Jas hujan',
            'is_critical' => true,
            'status' => PreparationStatus::NOT_CONFIRMED,
        ]);

        // Baris tanpa preparation_item_id (mis. SIMAKSI dan catatan khusus) boleh lebih dari satu.
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'preparation_item_id' => null,
            'category' => PreparationCategory::LOGISTICS,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::NOT_CONFIRMED,
        ]);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'preparation_item_id' => null,
            'category' => PreparationCategory::LOGISTICS,
            'label' => 'Lapor pos jaga',
            'is_critical' => false,
            'status' => PreparationStatus::NOT_CONFIRMED,
        ]);

        $this->expectException(QueryException::class);

        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'preparation_item_id' => $butir->id,
            'category' => PreparationCategory::EQUIPMENT,
            'label' => 'Jas hujan ganda',
            'is_critical' => true,
            'status' => PreparationStatus::NOT_CONFIRMED,
        ]);
    }

    public function test_negative_trail_and_mountain_metrics_are_rejected(): void
    {
        $berkasConstraint = base_path('database/migrations/2026_10_15_000300_add_non_negative_checks_and_unique_prep_constraint.php');
        $this->assertFileExists($berkasConstraint);
        $this->assertStringContainsString('trails_non_negative_metrics_chk', File::get($berkasConstraint));
        $this->assertStringContainsString('mountains_non_negative_elevation_chk', File::get($berkasConstraint));

        try {
            Mountain::factory()->create(['elevation_mdpl' => -50]);
            $this->fail('Ketinggian gunung negatif seharusnya ditolak.');
        } catch (InvalidArgumentException|QueryException) {
            $this->addToAssertionCount(1);
        }

        try {
            Trail::factory()->create(['distance_km' => -2.5]);
            $this->fail('Jarak jalur negatif seharusnya ditolak.');
        } catch (InvalidArgumentException|QueryException) {
            $this->addToAssertionCount(1);
        }

        try {
            Trail::factory()->create(['elevation_gain_m' => -100]);
            $this->fail('Tanjakan jalur negatif seharusnya ditolak.');
        } catch (InvalidArgumentException|QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_lazy_loading_is_prevented_outside_production(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());

        Trail::factory()->count(2)->create();
        $koleksi = Trail::query()->get();

        $this->expectException(LazyLoadingViolationException::class);
        $_ = $koleksi->first()->mountain;
    }

    public function test_route_fit_recommend_selects_scalar_columns_without_geometry_and_caps_candidates(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'completed_at' => now(),
        ]);

        $goal = HikingGoal::factory()->for($user)->create([
            'trip_type' => TripType::CAMPING->value,
        ]);

        Trail::factory()->count(5)->published()->easy()->create();

        config(['hiking.route_fit.candidate_limit' => 3]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $run = app(RouteFitService::class)->recommend($user, $goal);

        $kueri = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertSame(3, $run->results()->count());

        $kueriKandidat = $kueri->first(
            static fn (string $sql): bool => str_contains($sql, 'from "trails"') && str_contains($sql, '"checkpoints_count"'),
        );

        $this->assertNotNull($kueriKandidat);
        $this->assertStringNotContainsString('"trails".*', $kueriKandidat);
        $this->assertStringNotContainsString('select *,', strtolower($kueriKandidat));
        $this->assertStringNotContainsString('"geometry"', $kueriKandidat);
        $this->assertStringStartsWith('select "trails"."id"', $kueriKandidat);
    }
}
