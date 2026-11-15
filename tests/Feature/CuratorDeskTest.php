<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Meja kurator lewat HTTP (spec v3 bagian 10.7; rencana induk W8 butir 9; ruling W8-R15 dan
 * W8-R16): akses per peran, antrean yang hanya memuat kawasan kurator, formulir yang mengambil
 * pencatat dan penerbit dari sesi, alur kirim, verifikasi, dan persoalkan, batas laju, anggaran
 * kueri, dan draf yang tidak pernah terlihat pendaki.
 */
class CuratorDeskTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isianFormulir(array $ganti = []): array
    {
        return [
            'sasaran' => 'MOUNTAIN:'.$this->gunung->id,
            'otoritas' => $this->balai->id,
            'status' => OfficialStatusValue::CLOSED->value,
            'jenis_bukti' => 'OFFICIAL_LETTER',
            'url_sumber' => 'https://gedepangrango.org/pengumuman',
            'bukti' => $this->suratPdf(),
            'nomor_surat' => 'PG.12/BBTNGGP/2026',
            'tanggal_surat' => now()->subDay()->toDateString(),
            'mulai_berlaku' => now()->toDateString(),
            'cara_berakhir' => 'UNTIL_NOTICE',
            'golongan_alasan' => 'FIRE',
            'ringkasan' => 'Pendakian ditutup karena kebakaran hutan.',
            ...$ganti,
        ];
    }

    public function test_only_active_curators_open_the_desk(): void
    {
        $this->get(route('curator.desk'))->assertRedirect(route('login'));

        foreach ([UserRole::HIKER, UserRole::MODERATOR, UserRole::ADMIN] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran->value]))->get(route('curator.desk'))->assertForbidden();
        }

        $tanpaKeanggotaan = User::factory()->create(['role' => UserRole::CURATOR->value]);
        $this->actingAs($tanpaKeanggotaan)->get(route('curator.desk'))->assertForbidden();

        $this->actingAs($this->kurator())
            ->get(route('curator.desk'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Curator/Desk')
                ->where('auth.user.isKurator', true)
                ->where('badan.0.nama', 'Balai Besar TN Gede Pangrango (BBTNGGP)'));
    }

    public function test_the_desk_lists_only_its_area_and_never_asks_you_to_verify_yourself(): void
    {
        $saya = $this->kurator();
        $rekan = $this->kurator();
        $menungguSaya = app(TerbitStatus::class)->kirim($rekan, $this->draf($rekan, OfficialStatusValue::OPEN));
        app(TerbitStatus::class)->kirim($saya, $this->draf($saya, OfficialStatusValue::RESTRICTED, ['effective_at' => now()->addDay()]));
        $drafSaya = $this->draf($saya, OfficialStatusValue::CLOSED);
        $this->draf($rekan, OfficialStatusValue::CLOSED);

        $luar = Mountain::factory()->create();
        OfficialStatus::factory()->closed()->create(['statusable_type' => $luar->getMorphClass(), 'statusable_id' => $luar->id, 'scope' => 'MOUNTAIN', 'expires_at' => now()->subDay()]);
        $kedaluwarsa = $this->catatanLama(OfficialStatusValue::CLOSED, ['effective_at' => now()->subMonths(2), 'expires_at' => now()->subDay()], Trail::factory()->for($this->gunung)->create());

        $this->actingAs($saya)
            ->get(route('curator.desk'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('antrean.verifikasi', 1)
                ->where('antrean.verifikasi.0.id', $menungguSaya->id)
                ->has('antrean.draf', 1)
                ->where('antrean.draf.0.id', $drafSaya->id)
                ->has('antrean.tinjau', 1)
                ->where('antrean.tinjau.0.id', $kedaluwarsa->id)
                ->where('antrean.tinjau.0.alasanTinjau', 'Kedaluwarsa')
                ->where('antrean.tinjau.0.keadaan', 'Catatan lama'));
    }

    /**
     * Pencatat, penerbit, dan keadaan tidak pernah diambil dari isian, walau isiannya
     * menyelundupkannya; bukti diarsipkan dengan sidiknya.
     */
    public function test_a_draft_is_saved_with_who_and_on_whose_behalf_from_the_session(): void
    {
        // 10.00 WIB, tanggal UTC dan WIB sama: mulai berlaku dibaca menurut zona gunung.
        $this->travelTo(Carbon::parse('2026-10-08 03:00:00', 'UTC'));
        $kurator = $this->kurator();
        $lain = User::factory()->create();

        $this->actingAs($kurator)
            ->post(route('curator.statuses.store'), $this->isianFormulir([
                'recorded_by' => $lain->id,
                'verified_by' => $lain->id,
                'verification_state' => 'VERIFIED',
                'authority_id' => Authority::factory()->create()->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Draf tersimpan. Periksa temuan pemeriksaan sebelum mengirim.');

        $draf = OfficialStatus::sole();

        $this->assertSame($kurator->id, $draf->recorded_by);
        $this->assertNull($draf->verified_by);
        $this->assertSame($this->balai->id, $draf->authority_id);
        $this->assertSame(StatusVerificationState::DRAFT, $draf->verification_state);
        $this->assertSame('PG.12/BBTNGGP/2026', $draf->document_number);
        $this->assertNotNull($draf->evidence_sha256);
        $this->assertSame('2026-10-07 17:00:00', $draf->effective_at->utc()->format('Y-m-d H:i:s'), 'Tanggal mulai dibaca menurut zona gunung (00.00 WIB).');
    }

    /**
     * Niat lama OfficialStatusTargetTest (galat pada isiannya, bukan 404) berlaku di formulir meja
     * kurator, ditambah yurisdiksi.
     */
    public function test_a_target_or_body_outside_the_area_is_reported_on_its_field(): void
    {
        $kurator = $this->kurator();
        $luar = Mountain::factory()->create();
        $badanLain = tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach($this->gunung));

        $this->actingAs($kurator)->post(route('curator.statuses.store'), $this->isianFormulir(['sasaran' => 'MOUNTAIN:'.$luar->id]))
            ->assertSessionHasErrors(['sasaran' => 'Sasaran ini di luar kawasan badan Anda.']);
        $this->actingAs($kurator)->post(route('curator.statuses.store'), $this->isianFormulir(['otoritas' => $badanLain->id]))
            ->assertSessionHasErrors('otoritas');
        $this->actingAs($kurator)->post(route('curator.statuses.store'), $this->isianFormulir(['sasaran' => 'TRAIL:999999']))
            ->assertSessionHasErrors(['sasaran' => 'Sasaran itu tidak ditemukan. Pilih dari daftar.']);

        $this->assertSame(0, OfficialStatus::count());
    }

    public function test_the_flow_runs_through_http_and_the_recorder_cannot_verify(): void
    {
        $pencatat = $this->kurator();
        $pemeriksa = $this->kurator();
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $this->actingAs($pencatat)->post(route('curator.statuses.store'), $this->isianFormulir(['status' => 'OPEN', 'golongan_alasan' => '', 'ringkasan' => 'Pendakian dibuka kembali.']));
        $draf = OfficialStatus::where('verification_state', StatusVerificationState::DRAFT->value)->sole();

        $this->actingAs($pencatat)->post(route('curator.statuses.submit', $draf))
            ->assertRedirect(route('curator.statuses.show', $draf))
            ->assertSessionHas('status', 'Terkirim. Pendaki membacanya sesudah kurator kedua memverifikasi.');
        $this->actingAs($pencatat)->post(route('curator.statuses.verify', $draf))->assertForbidden();

        $this->actingAs($pemeriksa)->get(route('curator.statuses.show', $draf))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Curator/Status')
                ->where('catatan.keadaan', 'Menunggu verifikasi kedua')
                ->where('aksi.verifikasi', route('curator.statuses.verify', $draf))
                ->where('aksi.kirim', null));

        $this->actingAs($pemeriksa)->post(route('curator.statuses.verify', $draf))
            ->assertSessionHas('status', 'Terverifikasi dan terbaca pendaki.');
        $this->actingAs($pemeriksa)->post(route('curator.statuses.dispute', $draf), ['alasan' => 'pendek'])
            ->assertSessionHasErrors('alasan');
        $this->actingAs($pemeriksa)->post(route('curator.statuses.dispute', $draf), ['alasan' => 'Laman balai menarik pengumuman ini.'])
            ->assertSessionHas('status', 'Ditandai dipersoalkan dan tidak lagi terbaca pendaki.');

        $this->assertSame(StatusVerificationState::DISPUTED, $draf->fresh()->verification_state);
    }

    public function test_only_the_recorder_sees_a_draft(): void
    {
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::CLOSED);

        $this->actingAs($pencatat)->get(route('curator.statuses.show', $draf))->assertOk();
        $this->actingAs($pencatat)->get(route('curator.statuses.edit', $draf))->assertOk();
        $this->actingAs($this->kurator())->get(route('curator.statuses.show', $draf))->assertForbidden();
        $this->actingAs($this->kurator())->get(route('curator.evidence.show', $draf))->assertForbidden();
        $this->actingAs($this->kurator())->delete(route('curator.statuses.destroy', $draf))->assertForbidden();
        $this->assertModelExists($draf);
    }

    /**
     * Draf dan pembukaan yang belum diverifikasi tidak pernah sampai ke halaman pendaki.
     */
    public function test_drafts_and_unverified_openings_never_reach_hiker_pages(): void
    {
        // Status terlihat jalur ini Belum diketahui (catatan pabrik); draf Tutup atau pembukaan
        // yang menunggu verifikasi yang bocor akan mengubahnya menjadi Tutup atau Buka.
        $jalur = Trail::factory()->easy()->published()->for($this->gunung)->create();
        $pencatat = $this->kurator();
        $this->draf($pencatat, OfficialStatusValue::CLOSED, [], $jalur);
        app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::OPEN, [], $jalur));
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('statusResmi.status', null));
        $this->actingAs($pendaki)->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('trails.data.0.status.status', null));
        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('status.status', null)->where('jalur.0.status.status', null));
    }

    public function test_curator_writes_are_rate_limited_on_their_own_key(): void
    {
        $kurator = $this->kurator();

        foreach (range(1, (int) config('hiking.rate_limits.personal_writes_per_minute')) as $i) {
            $this->actingAs($kurator)->post(route('curator.statuses.store'), [])->assertSessionHasErrors();
        }

        $this->actingAs($kurator)->post(route('curator.statuses.store'), [])->assertStatus(429);
    }

    public function test_the_desk_does_not_grow_with_the_number_of_records(): void
    {
        $kurator = $this->kurator();
        $rekan = $this->kurator();
        $buat = function (int $n) use ($rekan): void {
            foreach (range(1, $n) as $i) {
                $jalur = Trail::factory()->for($this->gunung)->create();
                app(TerbitStatus::class)->kirim($rekan, $this->draf($rekan, OfficialStatusValue::OPEN, [], $jalur));
                $this->catatanLama(OfficialStatusValue::CLOSED, ['expires_at' => now()->subDay()], $jalur);
            }
        };

        $buat(2);
        $sedikit = $this->hitungKueri($kurator);
        $buat(10);

        $this->assertSame($sedikit, $this->hitungKueri($kurator));
    }

    private function hitungKueri(User $kurator): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });
        // Instans pengguna baru, seperti setiap permintaan sungguhan: relasi yang sudah dimuat
        // permintaan sebelumnya tidak mengurangi hitungan.
        $this->actingAs($kurator->fresh())->get(route('curator.desk'))->assertOk();
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }
}
