<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\CredentialLevel;
use App\Enums\SourceType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Authority;
use App\Models\DataSource;
use App\Models\ExpertCredential;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pintu masuk bagi pemandu bersertifikat untuk mengisi data jalur.
 *
 * Yang dijaga di sini bukan hanya bahwa pintunya terbuka, melainkan bahwa yang lain
 * tetap tertutup: pendaki biasa, sertifikat kedaluwarsa atau belum diverifikasi, kawasan
 * orang lain, dan penerbitan yang tetap milik admin.
 */
class TrailContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accredited_expert_can_open_the_page(): void
    {
        [$ahli] = $this->ahliDan();

        $this->actingAs($ahli)->get('/kontribusi')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/TrailContribution')
            ->where('title', 'Kontribusi Data Jalur')
            ->where('kredensial.0.jenjang', CredentialLevel::AHLI->label())
            ->where('isian', null));
    }

    public function test_an_ordinary_hiker_cannot(): void
    {
        $this->actingAs(User::factory()->create())->get('/kontribusi')->assertForbidden();
    }

    public function test_an_expert_whose_certificate_expired_cannot(): void
    {
        [$ahli] = $this->ahliDan();

        $this->travel(4)->years();

        $this->actingAs($ahli->fresh())->get('/kontribusi')->assertForbidden();
    }

    public function test_an_unverified_certificate_does_not_open_the_door(): void
    {
        [$pemandu] = $this->ahliDan(status: VerificationStatus::UNVERIFIED);

        $this->actingAs($pemandu)->get('/kontribusi')->assertForbidden();
    }

    public function test_the_page_only_lists_trails_in_the_covered_area(): void
    {
        [$ahli] = $this->ahliDan();
        Trail::factory()->for(Mountain::factory())->create(['name' => 'Jalur Kawasan Lain']);

        $this->actingAs($ahli)->get('/kontribusi')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails', 1)
            ->where('trails.0.nama', 'Jalur Kawasan Saya'));
    }

    /**
     * Formulir dibuka lewat ?isi= dan diotorisasi di server dengan policy yang sama
     * dengan penyimpanan; jalur di luar kawasan ditolak sebelum isiannya dikirim.
     */
    public function test_the_form_opens_only_for_a_trail_the_expert_may_update(): void
    {
        [$ahli, $trail] = $this->ahliDan();
        $lain = Trail::factory()->for(Mountain::factory())->create();

        $this->actingAs($ahli)->get('/kontribusi?isi='.$trail->id)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('isian.id', $trail->id)
            ->where('isian.nama', 'Jalur Kawasan Saya')
            ->where('isian.catatan_sumber', ''));

        $this->actingAs($ahli)->get('/kontribusi?isi='.$lain->id)->assertForbidden();
    }

    public function test_saving_fills_the_trail_characteristics(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->simpan($ahli, $trail)->assertRedirect(route('contribute'))->assertSessionHasNoErrors();

        $segar = $trail->fresh();
        $this->assertSame('11.50', $segar->distance_km);
        $this->assertSame(1300, $segar->elevation_gain_m);
        $this->assertSame(600, $segar->estimated_duration_minutes);
    }

    /**
     * §60: siapa menyumbang atas dasar sertifikat mana harus tersimpan, bukan
     * disimpulkan belakangan.
     */
    public function test_the_contribution_records_who_and_on_what_basis(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->simpan($ahli, $trail);
        $segar = $trail->fresh();

        $this->assertSame($ahli->id, $segar->contributed_by);
        $this->assertNotNull($segar->contributed_credential_id);
        $this->assertNotNull($segar->contributed_at);
        $this->assertSame(SourceType::ACCREDITED_EXPERT, $segar->dataSource->source_type);
    }

    /**
     * §92: data ahli bersertifikat bukan pernyataan pengelola. Menandainya OFFICIAL akan
     * melebihkan otoritasnya (R-009).
     */
    public function test_the_source_is_never_marked_official(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->simpan($ahli, $trail);

        $this->assertNotSame(SourceType::OFFICIAL, $trail->fresh()->dataSource->source_type);
    }

    /**
     * Angka lapangan tanpa asal tidak dapat dinilai admin yang meninjaunya.
     */
    public function test_the_source_note_is_required(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->simpan($ahli, $trail, ['catatan_sumber' => ''])->assertSessionHasErrors('catatan_sumber');

        // Factory sudah mengisi jaraknya, jadi yang dibuktikan adalah nilainya tidak
        // berubah menjadi angka yang diisikan tanpa catatan sumber.
        $this->assertNotSame('11.50', $trail->fresh()->distance_km);
    }

    /**
     * Inti pembatasannya. Gerbang §110 tidak berpindah tangan: mengisi data tidak pernah
     * berarti menerbitkannya, termasuk ketika klien menyelipkan is_published.
     */
    public function test_contributing_never_publishes_the_trail(): void
    {
        [$ahli, $trail] = $this->ahliDan(published: false);

        $this->simpan($ahli, $trail, ['is_published' => true, 'contributed_by' => 999]);

        $segar = $trail->fresh();
        $this->assertFalse($segar->is_published);
        $this->assertSame($ahli->id, $segar->contributed_by);
    }

    public function test_the_expert_is_told_that_publishing_is_not_theirs(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->simpan($ahli, $trail)->assertSessionHas('status', fn (string $pesan) => str_contains($pesan, 'admin yang memutuskan'));
    }

    /**
     * Kredensial dapat dicabut di antara membuka formulir dan menekan simpan.
     */
    public function test_a_credential_revoked_mid_session_stops_the_save(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->actingAs($ahli)->get('/kontribusi?isi='.$trail->id)->assertOk();

        ExpertCredential::query()->update(['verification_status' => VerificationStatus::DISPUTED->value]);

        $this->simpan($ahli, $trail)->assertForbidden();

        $this->assertNotSame('11.50', $trail->fresh()->distance_km, 'Kredensial yang dicabut tidak boleh sempat menyimpan.');
    }

    /**
     * Kredensial disahkan per kawasan. Id jalur kawasan lain yang ditebak tetap ditolak.
     */
    public function test_an_expert_cannot_save_a_trail_outside_their_area(): void
    {
        [$ahli] = $this->ahliDan();
        $lain = Trail::factory()->for(Mountain::factory())->create();
        $jarakAwal = $lain->distance_km;

        $this->simpan($ahli, $lain)->assertForbidden();

        $this->assertSame($jarakAwal, $lain->fresh()->distance_km);
    }

    public function test_every_contribution_lands_in_the_audit_trail(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->simpan($ahli, $trail);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $ahli->id,
            'action' => 'trail.contributed',
        ]);
    }

    /**
     * Versi Livewire menanyakan jumlah pos dan status resmi satu per jalur. Kini dimuat
     * sekaligus, jadi jumlah query tidak tumbuh bersama jumlah jalur di kawasannya.
     */
    public function test_the_page_does_not_grow_with_the_number_of_trails(): void
    {
        [$ahli, $trail] = $this->ahliDan();
        $this->actingAs($ahli)->get('/kontribusi');
        $sedikit = $this->hitungQuery('/kontribusi');

        Trail::factory()->count(6)->for($trail->mountain)->create();

        $this->assertSame($sedikit, $this->hitungQuery('/kontribusi'));
    }

    /**
     * Regresi 25 September 2026: ahli yang mengosongkan data kritis jalur yang sudah
     * terbit memicu PublicationGateViolation, yang lolos sebagai galat 500. Kini ahli
     * mendapat galat formulir yang menyebut apa yang kurang, jalurnya tidak berubah,
     * dan tidak ada sumber data yatim yang tertinggal (satu transaksi).
     */
    public function test_emptying_critical_data_of_a_published_trail_is_refused_not_a_server_error(): void
    {
        [$ahli, $draf] = $this->ahliDan();
        $trail = Trail::factory()->published()->for($draf->mountain)->create([
            'name' => 'Jalur Terbit Kawasan Saya',
            'distance_km' => 9,
            'elevation_gain_m' => 900,
            'estimated_duration_minutes' => 480,
        ]);
        $sumberSebelum = DataSource::count();

        $this->from(route('contribute'))
            ->simpan($ahli, $trail, ['distance_km' => null])
            ->assertRedirect(route('contribute'))
            ->assertSessionHasErrors('form');

        $this->assertStringContainsString('Karakteristik dasar belum lengkap', session('errors')->first('form'));
        $this->assertEquals(9, (float) $trail->fresh()->distance_km);
        $this->assertTrue($trail->fresh()->is_published);
        $this->assertSame($sumberSebelum, DataSource::count(), 'Sumber data yatim tertinggal setelah penolakan.');
    }

    private function hitungQuery(string $url): int
    {
        $jumlah = 0;
        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $this->get($url)->assertOk();

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function simpan(User $ahli, Trail $trail, array $ubah = []): TestResponse
    {
        return $this->actingAs($ahli)->post(route('contribute.store', $trail), array_merge([
            'distance_km' => 11.5,
            'elevation_gain_m' => 1300,
            'estimated_duration_minutes' => 600,
            'technical_demand' => 'MODERATE',
            'navigation_complexity' => 'MODERATE',
            'water_availability' => 'UNKNOWN',
            'terrain_character' => [],
            'camping_available' => false,
            'catatan_sumber' => 'Pengukuran lapangan Agustus 2026 bersama basecamp.',
        ], $ubah));
    }

    /**
     * @return array{0: User, 1: Trail}
     */
    private function ahliDan(
        CredentialLevel $level = CredentialLevel::AHLI,
        VerificationStatus $status = VerificationStatus::VERIFIED,
        bool $published = true,
    ): array {
        $gunung = Mountain::factory()->create();

        $trail = $published
            ? Trail::factory()->for($gunung)->create(['name' => 'Jalur Kawasan Saya'])
            : Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Jalur Kawasan Saya']);

        $penerbit = Authority::create([
            'name' => 'APGI uji',
            'slug' => 'apgi-uji',
            'abbreviation' => 'APGI',
            'type' => AuthorityType::PROFESSIONAL_ASSOCIATION->value,
        ]);

        $user = User::factory()->create(['role' => UserRole::HIKER->value]);

        $kredensial = ExpertCredential::create([
            'user_id' => $user->id,
            'issuing_authority_id' => $penerbit->id,
            'level' => $level->value,
            'certificate_number' => 'UJI/2026/0003',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
            'verification_status' => $status->value,
            'verified_at' => now(),
        ]);

        $kredensial->mountains()->attach($gunung->id);

        return [$user->fresh(), $trail];
    }
}
