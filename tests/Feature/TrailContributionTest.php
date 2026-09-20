<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\CredentialLevel;
use App\Enums\SourceType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Livewire\Contribute\TrailContribution;
use App\Models\Authority;
use App\Models\ExpertCredential;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pintu masuk bagi pemandu bersertifikat untuk mengisi data jalur.
 *
 * Sampai putaran lalu haknya sudah ada tetapi pintunya belum: policy mengizinkan, namun
 * tidak ada halaman tempat mengisi. Yang dijaga di sini bukan hanya bahwa pintunya
 * terbuka, melainkan bahwa yang lain tetap tertutup.
 */
class TrailContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accredited_expert_can_open_the_page(): void
    {
        [$ahli] = $this->ahliDan();

        $this->actingAs($ahli)->get('/kontribusi')->assertOk();
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
        [$ahli, $trail] = $this->ahliDan();
        $lain = Trail::factory()->for(Mountain::factory())->create(['name' => 'Jalur Kawasan Lain']);

        Livewire::actingAs($ahli)
            ->test(TrailContribution::class)
            ->assertSee($trail->name)
            ->assertDontSee($lain->name);
    }

    public function test_saving_fills_the_trail_characteristics(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->isi($ahli, $trail);

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

        $this->isi($ahli, $trail);
        $segar = $trail->fresh();

        $this->assertSame($ahli->id, $segar->contributed_by);
        $this->assertNotNull($segar->contributed_credential_id);
        $this->assertNotNull($segar->contributed_at);
        $this->assertSame(SourceType::ACCREDITED_EXPERT, $segar->dataSource->source_type);
    }

    /**
     * §92: data ahli bersertifikat bukan pernyataan pengelola. Menandainya OFFICIAL akan
     * melebihkan otoritasnya.
     */
    public function test_the_source_is_never_marked_official(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->isi($ahli, $trail);

        $this->assertNotSame(SourceType::OFFICIAL, $trail->fresh()->dataSource->source_type);
    }

    /**
     * Angka lapangan tanpa asal tidak dapat dinilai admin yang meninjaunya.
     */
    public function test_the_source_note_is_required(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        Livewire::actingAs($ahli)
            ->test(TrailContribution::class)
            ->call('edit', $trail->id)
            ->set('distance_km', 11.5)
            ->set('catatan_sumber', null)
            ->call('simpan')
            ->assertHasErrors('catatan_sumber');

        // Factory sudah mengisi jaraknya, jadi yang dibuktikan adalah nilainya tidak
        // berubah menjadi angka yang diisikan tanpa catatan sumber.
        $this->assertNotSame('11.50', $trail->fresh()->distance_km);
    }

    /**
     * Inti pembatasannya. Gerbang §110 tidak berpindah tangan: mengisi data tidak pernah
     * berarti menerbitkannya.
     */
    public function test_contributing_never_publishes_the_trail(): void
    {
        [$ahli, $trail] = $this->ahliDan(published: false);

        $this->isi($ahli, $trail);

        $this->assertFalse($trail->fresh()->is_published);
    }

    public function test_the_expert_is_told_that_publishing_is_not_theirs(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        Livewire::actingAs($ahli)
            ->test(TrailContribution::class)
            ->call('edit', $trail->id)
            ->set('distance_km', 11.5)
            ->set('catatan_sumber', 'Pengukuran lapangan Agustus 2026 bersama basecamp.')
            ->call('simpan')
            ->assertSee('admin yang memutuskan');
    }

    /**
     * Kredensial dapat dicabut di antara membuka formulir dan menekan simpan.
     */
    public function test_a_credential_revoked_mid_session_stops_the_save(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $component = Livewire::actingAs($ahli)
            ->test(TrailContribution::class)
            ->call('edit', $trail->id)
            ->set('distance_km', 11.5)
            ->set('catatan_sumber', 'Pengukuran lapangan Agustus 2026 bersama basecamp.');

        ExpertCredential::query()->update(['verification_status' => VerificationStatus::DISPUTED->value]);

        $component->call('simpan');

        $this->assertNotSame('11.50', $trail->fresh()->distance_km, 'Kredensial yang dicabut tidak boleh sempat menyimpan.');
    }

    public function test_every_contribution_lands_in_the_audit_trail(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->isi($ahli, $trail);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $ahli->id,
            'action' => 'trail.contributed',
        ]);
    }

    private function isi(User $ahli, Trail $trail): void
    {
        Livewire::actingAs($ahli)
            ->test(TrailContribution::class)
            ->call('edit', $trail->id)
            ->set('distance_km', 11.5)
            ->set('elevation_gain_m', 1300)
            ->set('estimated_duration_minutes', 600)
            ->set('catatan_sumber', 'Pengukuran lapangan Agustus 2026 bersama basecamp.')
            ->call('simpan');
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
