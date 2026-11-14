<?php

namespace Tests\Pratinjau;

use App\Enums\AuthorityType;
use App\Enums\CredentialLevel;
use App\Enums\ModerationStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Authority;
use App\Models\ExpertCredential;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\RouteFitService;
use Database\Seeders\DemoSeeder;

/**
 * Menutup 30 komponen halaman React yang belum dipotret `HalamanPratinjauTest` maupun
 * `HalamanGalatPratinjauTest` (rencana W9 Tugas 11 + `Admin/TrailSegments`), sehingga
 * `PreviewCoverageTest` membuktikan 100% komponen di `resources/js/pages` punya berkas
 * pratinjau untuk klik-lewat W9.
 */
class HalamanPenutupPratinjauTest extends PratinjauTestCase
{
    public function test_halaman_depan_dan_autentikasi_tamu(): void
    {
        $this->seed(DemoSeeder::class);

        $this->tulisPratinjau('depan', $this->get(self::ASAL.'/'), 'Welcome');
        $this->tulisPratinjau('masuk', $this->get(self::ASAL.'/login'), 'Auth/Login');
        $this->tulisPratinjau('daftar', $this->get(self::ASAL.'/register'), 'Auth/Register');
        $this->tulisPratinjau('lupa-sandi', $this->get(self::ASAL.'/forgot-password'), 'Auth/ForgotPassword');
        $this->tulisPratinjau('atur-ulang-sandi', $this->get(self::ASAL.'/reset-password/token-pratinjau?email=budi%40contoh.test'), 'Auth/ResetPassword');
    }

    public function test_verifikasi_email_dan_konfirmasi_sandi(): void
    {
        $this->seed(DemoSeeder::class);

        $belumVerifikasi = User::factory()->unverified()->create(['name' => 'Rina Kartika']);
        $this->actingAs($belumVerifikasi);
        $this->tulisPratinjau('verifikasi-email', $this->get(self::ASAL.'/verify-email'), 'Auth/VerifyEmail');

        $this->actingAs(User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail());
        $this->tulisPratinjau('konfirmasi-sandi', $this->get(self::ASAL.'/confirm-password'), 'Auth/ConfirmPassword');
    }

    public function test_tujuan_dan_hasil_rekomendasi(): void
    {
        $budi = $this->sebagaiBudi();
        $this->tulisPratinjau('tujuan-baru', $this->get(self::ASAL.'/goals/create'), 'Goals/Create');

        $tujuan = HikingGoal::factory()->for($budi)->create();
        $run = app(RouteFitService::class)->recommend($budi, $tujuan);

        $this->tulisPratinjau('hasil-rekomendasi', $this->get(self::ASAL.'/recommendations/'.$run->id), 'Recommendations/Show');
    }

    public function test_jalur_menunggu_data(): void
    {
        $this->sebagaiBudi();
        $gunung = Mountain::where('slug', 'gunung-gede')->firstOrFail();
        $menunggu = Trail::factory()->unpublished()->create([
            'mountain_id' => $gunung->id,
            'name' => 'Via Gunung Putri (draf kurasi)',
            'slug' => 'gede-via-gunung-putri-draf',
        ]);

        $this->tulisPratinjau('jalur-menunggu', $this->get(self::ASAL.'/trails/'.$menunggu->slug), 'Trails/Awaiting');
    }

    public function test_ubah_rencana_riwayat_dan_lapor_kondisi(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->tulisPratinjau('ubah-rencana', $this->get(self::ASAL.'/trips/'.$trip->id.'/edit'), 'Trips/Edit');
        $this->tulisPratinjau('riwayat', $this->get(self::ASAL.'/history'), 'Community/HikingHistory');
        $this->tulisPratinjau('lapor-kondisi', $this->get(self::ASAL.'/reports/create?trail='.$trip->trail_id), 'Community/ConditionReport');
    }

    public function test_kontribusi_ahli_bersertifikat(): void
    {
        $this->seed(DemoSeeder::class);
        $gunung = Mountain::where('slug', 'gunung-gede')->firstOrFail();
        $badan = Authority::factory()->create([
            'name' => 'LSP Pramuwisata Gunung Indonesia',
            'type' => AuthorityType::CERTIFICATION_BODY,
            'jurisdiction' => 'Nasional',
        ]);
        $ahli = User::factory()->create(['name' => 'Damar Prasetyo']);
        $kredensial = ExpertCredential::create([
            'user_id' => $ahli->id,
            'issuing_authority_id' => $badan->id,
            'scheme' => 'SKKNI-PEMANDU-GUNUNG',
            'certificate_number' => 'PRATINJAU-AHLI-001',
            'level' => CredentialLevel::AHLI,
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_at' => now()->subMonths(6),
        ]);
        $kredensial->mountains()->attach($gunung->id);

        $this->actingAs($ahli);
        $this->tulisPratinjau('kontribusi-ahli', $this->get(self::ASAL.'/kontribusi'), 'Community/TrailContribution');
    }

    public function test_antrean_moderasi(): void
    {
        $this->seed(DemoSeeder::class);
        $jalur = Trail::where('slug', 'gede-via-cibodas')->firstOrFail();
        $pelapor = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();

        TrailConditionReport::factory()->create([
            'trail_id' => $jalur->id,
            'user_id' => $pelapor->id,
            'moderation_status' => ModerationStatus::PENDING,
            'hike_date' => now()->subDay()->toDateString(),
            'note' => 'Jembatan kayu sebelum Pos Kandang Badak licin sesudah hujan sore.',
        ]);

        $moderator = User::factory()->create([
            'name' => 'Sari Wulandari',
            'role' => UserRole::MODERATOR,
        ]);
        $this->actingAs($moderator);

        $this->tulisPratinjau('antrean-moderasi', $this->get(self::ASAL.'/moderation'), 'Moderation/Queue');
    }

    public function test_konsol_admin_data_dan_tata_kelola(): void
    {
        $admin = $this->sebagaiAdmin();
        $gunung = Mountain::where('slug', 'gunung-gede')->firstOrFail();

        $this->tulisPratinjau('admin-gunung', $this->get(self::ASAL.'/admin/mountains'), 'Admin/Mountains');
        $this->tulisPratinjau('admin-foto-gunung', $this->get(self::ASAL.'/admin/mountains/'.$gunung->id.'/foto'), 'Admin/MountainPhoto');
        $this->tulisPratinjau('admin-jalur', $this->get(self::ASAL.'/admin/trails'), 'Admin/Trails');
        $this->tulisPratinjau('admin-status-resmi', $this->get(self::ASAL.'/admin/official-statuses'), 'Admin/OfficialStatuses');
        $this->tulisPratinjau('admin-sumber-data', $this->get(self::ASAL.'/admin/sources'), 'Admin/DataSources');
        $this->tulisPratinjau('admin-otoritas', $this->get(self::ASAL.'/admin/authorities'), 'Admin/Authorities');
        $this->tulisPratinjau('admin-kredensial', $this->get(self::ASAL.'/admin/credentials'), 'Admin/Credentials');
        $this->tulisPratinjau('admin-izin', $this->get(self::ASAL.'/admin/permits'), 'Admin/Permits');
        $this->assertSame(UserRole::ADMIN, $admin->role);
    }

    public function test_admin_pos_segmen_dan_geometri(): void
    {
        $this->sebagaiAdmin();
        $jalur = Trail::where('slug', 'gede-via-cibodas')->firstOrFail();
        TrailSegment::factory()->create([
            'trail_id' => $jalur->id,
            'sequence' => 1,
            'name' => 'Cibodas ke Kandang Badak',
        ]);

        $this->tulisPratinjau('admin-pos', $this->get(self::ASAL.'/jalur/'.$jalur->id.'/pos'), 'Admin/Checkpoints');
        $this->tulisPratinjau('admin-segmen', $this->get(self::ASAL.'/jalur/'.$jalur->id.'/segmen'), 'Admin/TrailSegments');
        $this->tulisPratinjau('admin-geometri', $this->get(self::ASAL.'/jalur/'.$jalur->id.'/geometri'), 'Admin/TrailGeometry');
    }

    public function test_admin_operasional_dan_kegunaan(): void
    {
        $this->sebagaiAdmin();

        $this->tulisPratinjau('admin-analitik', $this->get(self::ASAL.'/admin/analytics'), 'Admin/Analytics');
        $this->tulisPratinjau('admin-log-audit', $this->get(self::ASAL.'/admin/audit-logs'), 'Admin/AuditLogs');
        $this->tulisPratinjau('admin-kesehatan', $this->get(self::ASAL.'/admin/health'), 'Admin/Health');
        $this->tulisPratinjau('admin-kegunaan', $this->get(self::ASAL.'/admin/usability'), 'Admin/Usability');
    }

    private function sebagaiAdmin(): User
    {
        $this->seed(DemoSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Hendra Kusuma',
            'role' => UserRole::ADMIN,
        ]);
        $this->actingAs($admin);

        return $admin;
    }
}
