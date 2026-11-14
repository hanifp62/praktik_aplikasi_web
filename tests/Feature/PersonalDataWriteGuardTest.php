<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Enums\UserRole;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Penjaga keamanan tulis pada data pribadi, atribut hak istimewa pengguna, dan sapuan rute tulis W7
 * (rencana induk W7; CLAUDE.md bagian 4).
 */
class PersonalDataWriteGuardTest extends TestCase
{
    use RefreshDatabase;

    /** Nama rute tulis W7 dan kunci batas lajunya. */
    private const RUTE = [
        'progress.records.store' => 'catatan-pendakian',
        'progress.records.update' => 'catatan-pendakian',
        'progress.records.destroy' => 'catatan-pendakian',
        'progress.targets.update' => 'target-pribadi',
        'profile.items.store' => 'perlengkapan',
        'profile.items.update' => 'perlengkapan',
        'profile.items.destroy' => 'perlengkapan',
        'profile.photo.store' => 'foto-profil',
        'profile.photo.destroy' => 'foto-profil',
        'profile.tracks.destroy' => 'hapus-jejak',
        'trips.preparation.note' => 'catatan-butir',
    ];

    public function test_guests_cannot_write_personal_data_endpoints(): void
    {
        $this->patch(route('profile.update'), ['name' => 'Tamu'])->assertRedirect(route('login'));
        $this->put(route('profile.password'), [])->assertRedirect(route('login'));
        $this->put(route('profile.preference'), [])->assertRedirect(route('login'));
        $this->delete(route('profile.destroy'), [])->assertRedirect(route('login'));
        $this->post(route('onboarding.store'), [])->assertRedirect(route('login'));
        $this->post(route('trips.store'), [])->assertRedirect(route('login'));
        $this->post(route('reports.store'), [])->assertRedirect(route('login'));
    }

    public function test_user_role_cannot_be_mass_assigned_via_model_or_profile_update(): void
    {
        $dibuat = User::create([
            'name' => 'Pendaki Biasa',
            'email' => 'pendaki-uji@contoh.id',
            'password' => 'kata-sandi-kuat',
            'role' => UserRole::ADMIN->value,
        ]);

        $this->assertSame(UserRole::HIKER, $dibuat->fresh()->role);
        $this->assertFalse($dibuat->fresh()->isAdmin());

        $dibuat->fill(['role' => UserRole::MODERATOR->value])->save();
        $this->assertSame(UserRole::HIKER, $dibuat->fresh()->role);

        $this->actingAs($dibuat)
            ->patch(route('profile.update'), [
                'name' => 'Nama Diperbarui',
                'email' => $dibuat->email,
                'role' => UserRole::ADMIN->value,
            ])
            ->assertRedirect();

        $this->assertSame('Nama Diperbarui', $dibuat->fresh()->name);
        $this->assertSame(UserRole::HIKER, $dibuat->fresh()->role);
    }

    public function test_session_payload_encryption_defaults_on_in_production_when_unset(): void
    {
        $isi = (string) file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "'encrypt' => (bool) env('SESSION_ENCRYPT', env('APP_ENV') === 'production')",
            $isi,
        );
    }

    public function test_every_w7_write_route_requires_a_session_and_is_rate_limited_on_its_own_key(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (self::RUTE as $nama => $kunci) {
            $rute = Route::getRoutes()->getByName($nama);

            $this->assertNotNull($rute, "Rute {$nama} tidak terdaftar.");
            $this->assertContains('auth', $rute->gatherMiddleware(), "{$nama} terbuka untuk tamu.");
            $this->assertContains("throttle:{$batas},1,{$kunci}", $rute->gatherMiddleware(), "{$nama} tanpa batas laju sendiri.");
        }
    }

    public function test_a_guest_is_sent_to_sign_in_and_nothing_is_written(): void
    {
        $trip = TripPlan::factory()->create();

        $this->post(route('progress.records.store'), ['nama_jalur' => 'Lawu via Cemoro Sewu'])->assertRedirect(route('login'));
        $this->put(route('progress.targets.update'), ['tanjakan_m' => '1000', 'jarak_km' => '', 'pendakian' => ''])->assertRedirect(route('login'));
        $this->post(route('profile.items.store'), ['nama' => 'Headlamp'])->assertRedirect(route('login'));
        $this->delete(route('profile.photo.destroy'))->assertRedirect(route('login'));
        $this->delete(route('profile.tracks.destroy'))->assertRedirect(route('login'));
        $this->put(route('trips.preparation.note', [$trip, 1]), ['catatan' => 'Tamu'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('personal_targets', 0);
        $this->assertDatabaseCount('user_owned_items', 0);
        $this->assertSame(0, HikingHistory::where('source', HikeRecordSource::MANUAL->value)->count());
    }

    /**
     * Batas laju bekerja sungguhan, bukan hanya tercantum: kiriman ke-31 dalam semenit ditolak,
     * dan kunci rute lain tidak ikut habis.
     */
    public function test_the_limit_refuses_the_request_after_the_budget(): void
    {
        $user = User::factory()->create();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->delete(route('profile.tracks.destroy'))->assertRedirect(route('profile'));
        }

        $this->actingAs($user)->delete(route('profile.tracks.destroy'))->assertStatus(429);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => 'Headlamp'])->assertRedirect(route('profile'));
    }

    /**
     * Pemilik, asal catatan, dan keadaan selesai ditentukan server, bukan isian.
     */
    public function test_owner_and_origin_fields_in_the_payload_are_ignored(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $tripOrangLain = TripPlan::factory()->create(['user_id' => $orangLain->id]);
        $jalur = Trail::factory()->published()->easy()->create();

        $this->actingAs($saya)->post(route('progress.records.store'), [
            'trail_id' => $jalur->id,
            'tanggal' => now('Asia/Jakarta')->subDay()->toDateString(),
            'jenis' => 'TEKTOK',
            'user_id' => $orangLain->id,
            'source' => HikeRecordSource::TRIP->value,
            'trip_plan_id' => $tripOrangLain->id,
            'completion_state' => CompletionState::ABANDONED->value,
        ])->assertSessionHasNoErrors();

        $catatan = HikingHistory::sole();
        $this->assertSame([$saya->id, HikeRecordSource::MANUAL, null, CompletionState::COMPLETED], [
            $catatan->user_id,
            $catatan->source,
            $catatan->trip_plan_id,
            $catatan->completion_state,
        ]);

        $this->actingAs($saya)->post(route('profile.items.store'), ['nama' => 'Matras', 'user_id' => $orangLain->id])->assertSessionHasNoErrors();
        $this->assertSame(0, $orangLain->ownedItems()->count());
    }

    /**
     * Data orang lain tidak tersentuh walau id-nya diketahui: catatan pendakian, barang, dan
     * catatan butir persiapan menjawab 403 atau 404, dan tidak ada yang berubah.
     */
    public function test_another_hikers_records_cannot_be_reached_by_id(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $catatan = HikingHistory::factory()->manual()->for($orangLain)->create(['personal_notes' => 'Milik orang lain']);
        $barang = $orangLain->ownedItems()->create(['name' => 'Kompor lipat']);
        $trip = TripPlan::factory()->create(['user_id' => $orangLain->id]);
        $butir = $trip->preparationItems()->create(['category' => 'EQUIPMENT', 'label' => 'Tenda', 'is_critical' => false, 'status' => 'NOT_CONFIRMED']);

        $this->actingAs($saya)->put(route('progress.records.update', $catatan), ['nama_jalur' => 'Diambil', 'tanggal' => '2026-01-01', 'jenis' => 'TEKTOK'])->assertForbidden();
        $this->actingAs($saya)->delete(route('progress.records.destroy', $catatan))->assertForbidden();
        $this->actingAs($saya)->put(route('profile.items.update', $barang), ['nama' => 'Diambil'])->assertForbidden();
        $this->actingAs($saya)->delete(route('profile.items.destroy', $barang))->assertForbidden();
        $this->actingAs($saya)->put(route('trips.preparation.note', [$trip, $butir->id]), ['catatan' => 'Diambil'])->assertForbidden();

        $this->assertSame('Milik orang lain', $catatan->fresh()->personal_notes);
        $this->assertSame('Kompor lipat', $barang->fresh()->name);
        $this->assertNull($butir->fresh()->note);
    }
}
