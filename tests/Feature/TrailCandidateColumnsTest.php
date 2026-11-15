<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use App\Services\Trails\TrailSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kandidat jalur memilih kolomnya sendiri, tanpa garis rute (titipan W3 I7 dan T4-M5; ruling
 * W8-R20). Di SQLite kolom geometry memang tidak ada, jadi yang dibuktikan di sini adalah bentuk
 * kuerinya: daftar kolom bernama, bukan "trails.*". Bahwa kolom geografi benar-benar tidak terbawa
 * dibuktikan suite Spatial (TrailCandidateColumnsTest di tests/Spatial).
 */
class TrailCandidateColumnsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kolom baru di tabel trails harus masuk daftar ini, supaya tidak hilang diam-diam dari
     * kandidat Jelajah dan rekomendasi.
     */
    public function test_the_column_list_is_the_schema_without_geometry(): void
    {
        $this->assertEqualsCanonicalizing(Schema::getColumnListing('trails'), Trail::KOLOM_TANPA_GEOMETRI);
    }

    public function test_the_candidate_scope_names_every_column(): void
    {
        $sql = Trail::query()->published()->withPublishabilityData()->toSql();

        $this->assertStringNotContainsString('"trails".*', $sql);
        $this->assertStringStartsWith('select "trails"."id", "trails"."mountain_id"', $sql);

        foreach (Trail::KOLOM_TANPA_GEOMETRI as $kolom) {
            $this->assertStringContainsString('"trails"."'.$kolom.'"', $sql);
        }
    }

    /**
     * Jelajah, rekomendasi, dan simpanan: setiap kueri yang memuat baris jalur memilih kolom
     * bernama. "Jalur lain untuk Anda" memakai scope kandidat yang sama dengan rekomendasi.
     */
    public function test_explore_recommendations_and_saved_trails_load_named_columns(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);
        $jalur = Trail::factory()->published()->count(3)->create();
        foreach ($jalur as $j) {
            TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $j->id]);
        }

        DB::enableQueryLog();
        TrailSearch::dariInput([])->kandidat(10);
        app(RouteFitService::class)->recommend($user->fresh(), $goal);
        app(ConsiderationService::class)->simpanan($user);
        app(ConsiderationService::class)->forUser($user);
        $kueri = collect(DB::getQueryLog())->pluck('query')->map(fn (string $q) => self::tanpaSubkueri($q))->filter(fn (string $q) => preg_match('/ from "trails"( |$)/', $q) === 1)->values();
        DB::disableQueryLog();

        $this->assertGreaterThanOrEqual(4, $kueri->count(), 'Keempat jalur pemuatan kandidat harus terukur.');

        foreach ($kueri as $q) {
            $this->assertStringStartsWith('select "trails"."id"', $q, 'Kueri kandidat memuat semua kolom: '.$q);
        }
    }

    /**
     * Kueri tingkat atas saja: isi tanda kurung (subkueri hitungan, exists) dibuang.
     */
    private static function tanpaSubkueri(string $kueri): string
    {
        do {
            $kueri = (string) preg_replace('/\([^()]*\)/', '', $kueri, -1, $jumlah);
        } while ($jumlah > 0);

        return $kueri;
    }
}
