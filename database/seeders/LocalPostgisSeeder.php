<?php

namespace Database\Seeders;

use App\Enums\AuthorityMemberRole;
use App\Enums\CancellationReason;
use App\Enums\CheckpointType;
use App\Enums\CompletionState;
use App\Enums\ConditionTag;
use App\Enums\ExperienceLevel;
use App\Enums\HikeRecordSource;
use App\Enums\InterestRegion;
use App\Enums\ModerationStatus;
use App\Enums\NavigationComplexity;
use App\Enums\NavigationExperience;
use App\Enums\OfficialStatusValue;
use App\Enums\PreferredChallenge;
use App\Enums\PreferredDuration;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\TechnicalDemand;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Enums\VolcanoLevel;
use App\Enums\WaterAvailability;
use App\Exceptions\PostgresLokalDitolak;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\Trips\TripReminderScheduler;
use App\Support\PenjagaDataContoh;
use App\Support\PenjagaPostgresLokal;
use App\Support\Timezone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pengisi data lokal bergeometri PostGIS untuk pengujian di `php artisan serve`.
 *
 * Berbeda dari `DemoSeeder` (khusus berkas SQLite tanpa ekstensi PostGIS), seeder ini
 * hanya berjalan di PostgreSQL loopback lokal (`local` atau `testing`), memanggil seluruh
 * seeder dasar secara idempoten, memasang garis rute `LineString` dan titik pos `Point`
 * PostGIS pada jalur terbit, serta menyiapkan akun uji per peran dengan kata sandi acak
 * yang hanya dicetak ke konsol.
 */
class LocalPostgisSeeder extends Seeder
{
    public const EMAIL_PENDAKI = 'budi@contoh.test';

    public const EMAIL_KURATOR = 'kurator@contoh.test';

    public const EMAIL_MODERATOR = 'moderator@contoh.test';

    public const EMAIL_ADMIN = 'admin@contoh.test';

    public const PENANDA = 'DATA CONTOH';

    public function run(): void
    {
        $koneksi = DB::connection();
        $pengemudi = $koneksi->getDriverName();

        if ($pengemudi !== 'pgsql') {
            throw new PostgresLokalDitolak("LocalPostgisSeeder hanya berjalan di PostgreSQL; koneksi aktif sekarang {$pengemudi}.");
        }

        if (app()->environment('production', 'staging')) {
            throw new PostgresLokalDitolak('LocalPostgisSeeder tidak pernah dijalankan di lingkungan produksi atau staging.');
        }

        $host = (string) $koneksi->getConfig('host');

        if (! PenjagaPostgresLokal::adalahHostLoopback($host)) {
            throw new PostgresLokalDitolak("LocalPostgisSeeder menolak host selain loopback lokal ({$host}).");
        }

        (new PenjagaDataContoh)->putuskanSaluranLuar();

        $this->pastikanAkunDasarLokal();

        $this->call([
            RecommendationRuleSeeder::class,
            PreparationTemplateSeeder::class,
            MvpDatasetSeeder::class,
            MountainSeeder::class,
            OfficialStatusSeeder::class,
            PermitRequirementSeeder::class,
            AuthoritySeeder::class,
            AwaitingTrailSeeder::class,
        ]);

        $sumber = DataSource::updateOrCreate(
            ['source_name' => self::PENANDA.', bukan sumber resmi'],
            [
                'source_type' => SourceType::ADMIN_VERIFIED->value,
                'source_owner' => 'Tim Memount',
                'retrieved_at' => now(),
                'verified_at' => null,
                'freshness_policy' => 'Data contoh untuk pengujian lokal dengan PostGIS; tidak pernah dipakai di produksi.',
                'verification_status' => VerificationStatus::UNVERIFIED->value,
                'notes' => self::PENANDA.'. Nilai jalur, pos, dan status dibuat untuk pengujian lokal.',
            ]
        );

        $this->lengkapiGunungTambahan($sumber);

        foreach (Trail::with('checkpoints')->get() as $jalurDasar) {
            if ($jalurDasar->distance_km !== null && $jalurDasar->checkpoints->isNotEmpty()) {
                $this->terbitkanDenganGeometri($jalurDasar, $sumber, OfficialStatusValue::OPEN);
            }
        }

        $petaJalur = $this->tanamJalurContoh($sumber);
        $this->tanamStatusGunungDanVulkanik($sumber);
        $this->siapkanAkunPeran($petaJalur);
        $this->tanamLaporanKomunitas($petaJalur);
    }

    private function pastikanAkunDasarLokal(): void
    {
        foreach ([
            ['Admin Demo', 'admin@example.com', UserRole::ADMIN],
            ['Moderator Demo', 'moderator@example.com', UserRole::MODERATOR],
            ['Pendaki Demo', 'pendaki@example.com', UserRole::HIKER],
            ['Kurator Demo', 'kurator@example.com', UserRole::CURATOR],
        ] as [$nama, $surel, $peran]) {
            if (User::where('email', $surel)->doesntExist()) {
                User::factory()->create([
                    'name' => $nama,
                    'email' => $surel,
                    'role' => $peran->value,
                ]);
            }
        }
    }

    private function lengkapiGunungTambahan(DataSource $sumber): void
    {
        foreach ([
            ['Gunung Batu', 'Jawa Barat', 'Asia/Jakarta', 875, -6.58250, 106.99360],
            ['Gunung Batur', 'Bali', 'Asia/Makassar', 1717, -8.24190, 115.37530],
        ] as [$nama, $provinsi, $zona, $tinggi, $lintang, $bujur]) {
            $gunung = Mountain::updateOrCreate(
                ['slug' => Str::slug($nama)],
                [
                    'name' => $nama,
                    'province' => $provinsi,
                    'region' => $provinsi,
                    'timezone' => $zona,
                    'elevation_mdpl' => $tinggi,
                    'description' => self::PENANDA.". {$nama} di {$provinsi}, dipakai untuk peninjauan tampilan lokal.",
                    'data_source_id' => $sumber->id,
                ]
            );

            $gunung->setCoordinates($lintang, $bujur);
        }
    }

    /**
     * @return array<string, Trail>
     */
    private function tanamJalurContoh(DataSource $sumber): array
    {
        $petaJalur = [];

        foreach ($this->daftarJalurContoh() as $slug => $data) {
            $gunung = Mountain::where('slug', $data['gunung'])->firstOrFail();

            $jalur = Trail::updateOrCreate(
                ['slug' => $slug],
                [
                    'mountain_id' => $gunung->id,
                    'name' => $data['nama'],
                    'description' => self::PENANDA.'. '.$data['keterangan'],
                    'distance_km' => $data['jarak'],
                    'elevation_gain_m' => $data['naik'],
                    'elevation_loss_m' => null,
                    'elevation_profile' => $data['profil'],
                    'estimated_duration_minutes' => $data['menit'],
                    'technical_demand' => $data['teknis']->value,
                    'navigation_complexity' => $data['navigasi']->value,
                    'water_availability' => $data['air']->value,
                    'terrain_character' => $data['medan'],
                    'camping_available' => $data['kemah'],
                    'starting_point' => $data['awal'],
                    'weather_adm4_code' => $data['adm4'] ?? null,
                    'weather_reference_area' => $data['wilayah_cuaca'] ?? null,
                    'data_source_id' => $sumber->id,
                ]
            );

            foreach ($data['pos'] as $indeks => [$namaPos, $jenisPos, $elevasiPos]) {
                Checkpoint::updateOrCreate(
                    ['trail_id' => $jalur->id, 'sequence' => $indeks + 1],
                    [
                        'name' => $namaPos,
                        'checkpoint_type' => $jenisPos->value,
                        'elevation_m' => $elevasiPos,
                    ]
                );
            }

            $this->terbitkanDenganGeometri($jalur, $sumber, $data['status'], $data['alasan'] ?? null);
            $petaJalur[$slug] = $jalur->refresh();
        }

        return $petaJalur;
    }

    private function terbitkanDenganGeometri(
        Trail $jalur,
        DataSource $sumber,
        OfficialStatusValue $nilaiStatus,
        ?string $alasan = null
    ): void {
        $gunung = $jalur->mountain()->firstOrFail();
        $lintangPuncak = (float) ($gunung->latitude ?? -6.78833);
        $bujurPuncak = (float) ($gunung->longitude ?? 106.98167);

        $hash = crc32($jalur->slug);
        $sudut = (($hash % 360) * M_PI) / 180.0;
        $rentangDerajat = max(0.018, min(0.065, ((float) ($jalur->distance_km ?? 6.0)) / 180.0));
        $lintangAwal = $lintangPuncak + sin($sudut) * $rentangDerajat;
        $bujurAwal = $bujurPuncak + cos($sudut) * $rentangDerajat;

        $langkah = 12;
        $koordinat = [];

        for ($i = 0; $i <= $langkah; $i++) {
            $t = $i / $langkah;
            $belokan = sin($t * M_PI * 2.0) * 0.0022;
            $lat = $lintangAwal + ($lintangPuncak - $lintangAwal) * $t + $belokan * cos($sudut);
            $lon = $bujurAwal + ($bujurPuncak - $bujurAwal) * $t - $belokan * sin($sudut);
            $koordinat[] = [round($lon, 6), round($lat, 6)];
        }

        $jalur->writeLineString('geometry', $koordinat);

        $daftarPos = $jalur->checkpoints()->orderBy('sequence')->get();
        $jumlahPos = max(1, $daftarPos->count());

        foreach ($daftarPos as $urutan => $pos) {
            $t = $jumlahPos === 1 ? 1.0 : $urutan / ($jumlahPos - 1);
            $indeksKoordinat = min($langkah, (int) round($t * $langkah));
            [$bujurPos, $lintangPos] = $koordinat[$indeksKoordinat];
            $pos->setCoordinates($lintangPos, $bujurPos);
        }

        if (empty($jalur->elevation_profile)) {
            $elevasiAwal = (int) ($daftarPos->first()?->elevation_m ?? max(500, $gunung->elevation_mdpl - (int) ($jalur->elevation_gain_m ?? 800)));
            $elevasiAkhir = (int) ($daftarPos->last()?->elevation_m ?? $gunung->elevation_mdpl);
            $jarakTotal = (float) ($jalur->distance_km ?? 6.0);
            $profil = [];

            for ($i = 0; $i <= 6; $i++) {
                $t = $i / 6;
                $profil[] = [
                    'km' => round($jarakTotal * $t, 1),
                    'm' => (int) round($elevasiAwal + ($elevasiAkhir - $elevasiAwal) * pow($t, 1.15)),
                ];
            }

            $jalur->elevation_profile = $profil;
            $jalur->save();
        }

        if ($jalur->statusTerlihat()->count() === 0) {
            OfficialStatus::create([
                'statusable_type' => $jalur->getMorphClass(),
                'statusable_id' => $jalur->id,
                'scope' => StatusScope::TRAIL->value,
                'status' => $nilaiStatus->value,
                'data_source_id' => $sumber->id,
                'source' => self::PENANDA.', bukan status resmi',
                'published_at' => now()->subDays(5),
                'fetched_at' => now()->subDays(5),
                'effective_at' => now()->subDays(5),
                'verified_at' => now()->subDays(4),
                'reason' => $alasan !== null ? self::PENANDA.'. '.$alasan : null,
            ]);
        }

        $jalur->refresh();

        if ($jalur->publishabilityReport() === [] && ! $jalur->is_published) {
            $jalur->update(['is_published' => true]);
        }
    }

    private function tanamStatusGunungDanVulkanik(DataSource $sumber): void
    {
        $gede = Mountain::where('slug', 'gunung-gede')->firstOrFail();

        OfficialStatus::updateOrCreate(
            [
                'statusable_type' => $gede->getMorphClass(),
                'statusable_id' => $gede->id,
                'source' => self::PENANDA.', bukan status resmi',
            ],
            [
                'scope' => StatusScope::MOUNTAIN->value,
                'status' => OfficialStatusValue::OPEN->value,
                'data_source_id' => $sumber->id,
                'published_at' => now()->subDays(3),
                'fetched_at' => now()->subDays(3),
                'effective_at' => now()->subDays(3),
                'verified_at' => now()->subDays(2),
            ]
        );

        if (VolcanoStatus::where('mountain_id', $gede->id)->doesntExist()) {
            $vulkanik = new VolcanoStatus([
                'level' => VolcanoLevel::NORMAL->value,
                'recommendation' => self::PENANDA.'. Pendaki dan wisatawan tidak mendekati kawah aktif dalam radius 1 km.',
                'report_url' => 'https://magma.esdm.go.id/',
                'issued_at' => now()->subHours(5),
            ]);
            $vulkanik->forceFill(['mountain_id' => $gede->id, 'verified_at' => now()->subHours(2)])->save();
        }
    }

    /**
     * @param  array<string, Trail>  $petaJalur
     */
    private function siapkanAkunPeran(array $petaJalur): void
    {
        $sandiBudi = Str::password(20, symbols: false);
        $budi = User::updateOrCreate(
            ['email' => self::EMAIL_PENDAKI],
            ['name' => 'Budi Setiawan', 'password' => $sandiBudi]
        );
        $budi->forceFill(['role' => UserRole::HIKER->value, 'email_verified_at' => now()])->save();
        $this->isiDataPendaki($budi, $petaJalur);

        $pendakiDemo = User::where('email', 'pendaki@example.com')->first();

        if ($pendakiDemo !== null) {
            $this->isiDataPendaki($pendakiDemo, $petaJalur);
        }

        $adminRujukan = User::where('email', 'admin@example.com')->firstOrFail();

        $kuratorDemo = User::where('email', 'kurator@example.com')->first();

        if ($kuratorDemo !== null) {
            $this->ikatKuratorGedePangrango($kuratorDemo, $adminRujukan);
        }

        $sandiKurator = Str::password(20, symbols: false);
        $kuratorContoh = User::updateOrCreate(
            ['email' => self::EMAIL_KURATOR],
            ['name' => 'Kurator Contoh Gede Pangrango', 'password' => $sandiKurator]
        );
        $this->ikatKuratorGedePangrango($kuratorContoh, $adminRujukan);

        $sandiModerator = Str::password(20, symbols: false);
        $moderatorContoh = User::updateOrCreate(
            ['email' => self::EMAIL_MODERATOR],
            ['name' => 'Moderator Contoh', 'password' => $sandiModerator]
        );
        $moderatorContoh->forceFill(['role' => UserRole::MODERATOR->value, 'email_verified_at' => now()])->save();

        $sandiAdmin = Str::password(20, symbols: false);
        $adminContoh = User::updateOrCreate(
            ['email' => self::EMAIL_ADMIN],
            ['name' => 'Admin Contoh', 'password' => $sandiAdmin]
        );
        $adminContoh->forceFill(['role' => UserRole::ADMIN->value, 'email_verified_at' => now()])->save();

        $this->command?->info('Akun contoh lokal dengan kata sandi acak sesi ini:');
        $this->command?->line('  Pendaki   : '.self::EMAIL_PENDAKI." / {$sandiBudi}");
        $this->command?->line('  Kurator   : '.self::EMAIL_KURATOR." / {$sandiKurator}");
        $this->command?->line('  Moderator : '.self::EMAIL_MODERATOR." / {$sandiModerator}");
        $this->command?->line('  Admin     : '.self::EMAIL_ADMIN." / {$sandiAdmin}");
    }

    /**
     * @param  array<string, Trail>  $petaJalur
     */
    private function isiDataPendaki(User $pendaki, array $petaJalur): void
    {
        $pendaki->forceFill(['email_verified_at' => $pendaki->email_verified_at ?? now()])->save();

        $pendaki->profile()->updateOrCreate(
            ['user_id' => $pendaki->id],
            [
                'experience_level' => ExperienceLevel::BEGINNER->value,
                'region_preference' => 'Jawa Barat',
                'completed_at' => now(),
            ]
        );

        $pendaki->experience()->updateOrCreate(
            ['user_id' => $pendaki->id],
            [
                'completed_hikes_count' => 3,
                'terrain_experience' => ['FOREST', 'ROCKY'],
                'navigation_experience' => NavigationExperience::BASIC->value,
                'longest_hike_duration_minutes' => 300,
                'highest_elevation_gain_m' => 700,
            ]
        );

        $pendaki->preference()->updateOrCreate(
            ['user_id' => $pendaki->id],
            [
                'preferred_duration' => PreferredDuration::TWO_DAY->value,
                'preferred_trip_type' => TripType::CAMPING->value,
                'preferred_challenge' => PreferredChallenge::MODERATE->value,
                'region_preference' => 'Jawa Barat',
                'interest_regions' => [InterestRegion::JAWA_BARAT_BANTEN->value, InterestRegion::JAWA_TENGAH_DIY->value],
                'record_track' => false,
            ]
        );

        if ($pendaki->ownedItems()->doesntExist()) {
            $pendaki->ownedItems()->createMany(array_map(
                fn (string $nama): array => ['name' => $nama],
                ['Headlamp', 'Jas hujan', 'Jaket hangat', 'Sepatu mendaki', 'Kompor lipat']
            ));
        }

        if ($pendaki->personalTargets()->doesntExist()) {
            $pendaki->personalTargets()->createMany([
                ['metric' => 'GAIN', 'monthly_value' => 1000],
                ['metric' => 'HIKES', 'monthly_value' => 1],
            ]);
        }

        $hariIni = Carbon::parse(Timezone::earliestDateInIndonesia());

        if ($pendaki->tripPlans()->doesntExist()) {
            $rencanaGede = TripPlan::create([
                'user_id' => $pendaki->id,
                'trail_id' => $petaJalur['gede-via-cibodas']->id,
                'name' => 'Gede via Cibodas',
                'planned_date' => $hariIni->copy()->addDays(15)->toDateString(),
                'end_date' => $hariIni->copy()->addDays(16)->toDateString(),
                'start_time' => '07:00',
                'participants_count' => 3,
                'trip_type' => TripType::CAMPING->value,
                'notes' => self::PENANDA.'. Berangkat bersama Dimas dan Sari; kumpul di Cibodas Jumat malam.',
                'reminders_enabled' => true,
                'status' => TripStatus::PLANNED->value,
            ]);
            app(TripReminderScheduler::class)->sinkronkan($rencanaGede);

            foreach ([
                ['andong-via-sawit', 120, TripType::TEKTOK],
                ['batur-via-toya-bungkah', 75, TripType::TEKTOK],
                ['prau-via-wates', 40, TripType::CAMPING],
            ] as [$slug, $hariLalu, $jenis]) {
                $tanggal = $hariIni->copy()->subDays($hariLalu);
                $selesai = $tanggal->copy()->setTime(16, 0);

                $rencana = TripPlan::create([
                    'user_id' => $pendaki->id,
                    'trail_id' => $petaJalur[$slug]->id,
                    'name' => $petaJalur[$slug]->name,
                    'planned_date' => $tanggal->toDateString(),
                    'trip_type' => $jenis->value,
                    'status' => TripStatus::COMPLETED->value,
                    'completed_at' => $selesai,
                ]);

                HikingHistory::create([
                    'user_id' => $pendaki->id,
                    'trip_plan_id' => $rencana->id,
                    'trail_id' => $petaJalur[$slug]->id,
                    'trip_type' => $jenis->value,
                    'completion_state' => CompletionState::COMPLETED->value,
                    'completed_at' => $selesai,
                ]);
            }

            TripPlan::create([
                'user_id' => $pendaki->id,
                'trail_id' => $petaJalur['pangrango-via-cibodas']->id,
                'name' => 'Pangrango akhir pekan',
                'planned_date' => $hariIni->copy()->subDays(20)->toDateString(),
                'trip_type' => TripType::CAMPING->value,
                'notes' => self::PENANDA.'. Dibatalkan karena cuaca.',
                'status' => TripStatus::CANCELLED->value,
                'cancellation_reason' => CancellationReason::WEATHER->value,
            ]);

            HikingHistory::create([
                'user_id' => $pendaki->id,
                'source' => HikeRecordSource::MANUAL->value,
                'trip_plan_id' => null,
                'trail_id' => $petaJalur['papandayan-via-camp-david']->id,
                'trip_type' => TripType::TEKTOK->value,
                'completion_state' => CompletionState::COMPLETED->value,
                'completed_at' => $hariIni->copy()->subDays(3)->setTime(12, 0),
                'recorded_gain_m' => 610,
                'recorded_distance_m' => 14800,
                'recorded_moving_minutes' => 330,
            ]);
        }

        foreach (['batu-via-jonggol', 'gede-via-cibodas', 'gede-via-gunung-putri', 'pangrango-via-cibodas', 'gede-via-selabintana'] as $slug) {
            TrailConsideration::firstOrCreate([
                'user_id' => $pendaki->id,
                'trail_id' => $petaJalur[$slug]->id,
            ]);
        }
    }

    private function ikatKuratorGedePangrango(User $kurator, User $adminRujukan): void
    {
        $kurator->forceFill(['role' => UserRole::CURATOR->value, 'email_verified_at' => now()])->save();

        $balai = Authority::where('slug', 'btn-gede-pangrango')->firstOrFail();
        $anggota = AuthorityMember::firstOrNew([
            'user_id' => $kurator->id,
            'authority_id' => $balai->id,
        ]);

        $anggota->fill([
            'role' => AuthorityMemberRole::KURATOR->value,
            'appointment_reference' => self::PENANDA.' SK-LOKAL-01',
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
        ]);

        $anggota->forceFill([
            'user_id' => $kurator->id,
            'authority_id' => $balai->id,
            'granted_by' => $adminRujukan->id,
        ])->save();
    }

    /**
     * @param  array<string, Trail>  $petaJalur
     */
    private function tanamLaporanKomunitas(array $petaJalur): void
    {
        $pelapor = User::firstOrCreate(
            ['email' => 'sari@contoh.test'],
            ['name' => 'Sari Wulandari', 'password' => Str::password(20, symbols: false)]
        );

        TrailConditionReport::firstOrCreate(
            [
                'trail_id' => $petaJalur['gede-via-cibodas']->id,
                'user_id' => $pelapor->id,
                'moderation_status' => ModerationStatus::APPROVED->value,
            ],
            [
                'hike_date' => Carbon::parse(Timezone::earliestDateInIndonesia())->subDays(2)->toDateString(),
                'condition_tags' => [ConditionTag::SLIPPERY->value, ConditionTag::MUDDY->value],
                'note' => self::PENANDA.'. Licin dan berlumpur antara Air Panas dan Kandang Badak setelah hujan sore.',
                'moderated_at' => now(),
            ]
        );

        TrailConditionReport::firstOrCreate(
            [
                'trail_id' => $petaJalur['gede-via-gunung-putri']->id,
                'user_id' => $pelapor->id,
                'moderation_status' => ModerationStatus::PENDING->value,
            ],
            [
                'hike_date' => Carbon::parse(Timezone::earliestDateInIndonesia())->subDay()->toDateString(),
                'condition_tags' => [ConditionTag::DRY->value, ConditionTag::GOOD_MARKING->value],
                'note' => self::PENANDA.'. Jalur bersih sampai Surya Kencana, angin cukup kencang sore hari.',
            ]
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function daftarJalurContoh(): array
    {
        return [
            'gede-via-cibodas' => [
                'gunung' => 'gunung-gede', 'nama' => 'Gede via Cibodas', 'jarak' => 9.7, 'naik' => 1650, 'menit' => 450,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'ROCKY', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Cibodas',
                'adm4' => '32.03.06.2002', 'wilayah_cuaca' => 'Cipanas, Cianjur',
                'keterangan' => 'Jalur lewat Telaga Biru, aliran air panas, dan Kandang Badak.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Pintu Cibodas', CheckpointType::BASECAMP, 1340],
                    ['Telaga Biru', CheckpointType::POS, 1575],
                    ['Panyancangan', CheckpointType::JUNCTION, 1620],
                    ['Air Panas', CheckpointType::POS, 2150],
                    ['Kandang Badak', CheckpointType::CAMPGROUND, 2395],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
                'profil' => [
                    ['km' => 0, 'm' => 1340], ['km' => 1.5, 'm' => 1575], ['km' => 2.8, 'm' => 1620],
                    ['km' => 5.3, 'm' => 2150], ['km' => 7.2, 'm' => 2395], ['km' => 9.7, 'm' => 2958],
                ],
            ],
            'gede-via-gunung-putri' => [
                'gunung' => 'gunung-gede', 'nama' => 'Gede via Gunung Putri', 'jarak' => 7.2, 'naik' => 1720, 'menit' => 390,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['FOREST', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Basecamp Gunung Putri',
                'adm4' => '32.03.28.2003', 'wilayah_cuaca' => 'Pacet, Cianjur',
                'keterangan' => 'Jalur pendek yang menanjak terus sampai Alun-alun Surya Kencana.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Gunung Putri', CheckpointType::BASECAMP, 1450],
                    ['Alun-alun Surya Kencana', CheckpointType::CAMPGROUND, 2750],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
                'profil' => [['km' => 0, 'm' => 1450], ['km' => 2.5, 'm' => 1900], ['km' => 5.0, 'm' => 2750], ['km' => 7.2, 'm' => 2958]],
            ],
            'gede-via-selabintana' => [
                'gunung' => 'gunung-gede', 'nama' => 'Gede via Selabintana', 'jarak' => 11.8, 'naik' => 1900, 'menit' => 660,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::HIGH, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'MUD', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Selabintana',
                'keterangan' => 'Jalur terpanjang ke Gede dari sisi Sukabumi.',
                'status' => OfficialStatusValue::RESTRICTED, 'alasan' => 'Perbaikan jembatan',
                'pos' => [
                    ['Pintu Selabintana', CheckpointType::BASECAMP, 1100],
                    ['Alun-alun Surya Kencana', CheckpointType::CAMPGROUND, 2750],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
                'profil' => [['km' => 0, 'm' => 1100], ['km' => 4.0, 'm' => 1700], ['km' => 8.0, 'm' => 2400], ['km' => 10.0, 'm' => 2750], ['km' => 11.8, 'm' => 2958]],
            ],
            'pangrango-via-cibodas' => [
                'gunung' => 'gunung-pangrango', 'nama' => 'Pangrango via Cibodas', 'jarak' => 11.2, 'naik' => 1680, 'menit' => 510,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Cibodas',
                'keterangan' => 'Berpisah dari jalur Gede di Kandang Badak, lalu hutan rapat sampai puncak.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Pintu Cibodas', CheckpointType::BASECAMP, 1340],
                    ['Kandang Badak', CheckpointType::CAMPGROUND, 2395],
                    ['Puncak Pangrango', CheckpointType::SUMMIT, 3019],
                ],
                'profil' => [['km' => 0, 'm' => 1340], ['km' => 5.3, 'm' => 2150], ['km' => 7.2, 'm' => 2395], ['km' => 11.2, 'm' => 3019]],
            ],
            'papandayan-via-camp-david' => [
                'gunung' => 'gunung-papandayan', 'nama' => 'Papandayan via Camp David', 'jarak' => 7.4, 'naik' => 610, 'menit' => 270,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::ABUNDANT,
                'medan' => ['ROCKY', 'FOREST'], 'kemah' => true, 'awal' => 'Camp David',
                'keterangan' => 'Melewati kawah lalu padang Pondok Saladah.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Camp David', CheckpointType::BASECAMP, 2050],
                    ['Pondok Saladah', CheckpointType::CAMPGROUND, 2288],
                    ['Tegal Alun', CheckpointType::POS, 2600],
                ],
                'profil' => [['km' => 0, 'm' => 2050], ['km' => 2.5, 'm' => 2288], ['km' => 5.0, 'm' => 2450], ['km' => 7.4, 'm' => 2600]],
            ],
            'batu-via-jonggol' => [
                'gunung' => 'gunung-batu', 'nama' => 'Batu via Jonggol', 'jarak' => 1.6, 'naik' => 280, 'menit' => 90,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['ROCKY'], 'kemah' => false, 'awal' => 'Basecamp Gunung Batu',
                'keterangan' => 'Pendakian singkat ke puncak batu di Bogor timur.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Gunung Batu', CheckpointType::BASECAMP, 600],
                    ['Puncak Gunung Batu', CheckpointType::SUMMIT, 875],
                ],
                'profil' => [['km' => 0, 'm' => 600], ['km' => 0.8, 'm' => 760], ['km' => 1.6, 'm' => 875]],
            ],
            'prau-via-wates' => [
                'gunung' => 'gunung-prau', 'nama' => 'Prau via Wates', 'jarak' => 6.1, 'naik' => 700, 'menit' => 270,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'SAVANNA'], 'kemah' => true, 'awal' => 'Basecamp Wates',
                'keterangan' => 'Jalur hutan yang lebih sepi menuju sabana Prau.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Wates', CheckpointType::BASECAMP, 1890],
                    ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                ],
                'profil' => [['km' => 0, 'm' => 1890], ['km' => 2.0, 'm' => 2150], ['km' => 4.0, 'm' => 2400], ['km' => 6.1, 'm' => 2590]],
            ],
            'andong-via-sawit' => [
                'gunung' => 'gunung-andong', 'nama' => 'Andong via Sawit', 'jarak' => 2.8, 'naik' => 400, 'menit' => 120,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['FOREST', 'EXPOSED_RIDGE'], 'kemah' => true, 'awal' => 'Basecamp Sawit',
                'keterangan' => 'Pendakian pendek ke punggungan terbuka.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Sawit', CheckpointType::BASECAMP, 1340],
                    ['Puncak Andong', CheckpointType::SUMMIT, 1726],
                ],
                'profil' => [['km' => 0, 'm' => 1340], ['km' => 1.4, 'm' => 1550], ['km' => 2.8, 'm' => 1726]],
            ],
            'batur-via-toya-bungkah' => [
                'gunung' => 'gunung-batur', 'nama' => 'Batur via Toya Bungkah', 'jarak' => 4.6, 'naik' => 630, 'menit' => 150,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['SAND', 'ROCKY'], 'kemah' => false, 'awal' => 'Toya Bungkah',
                'keterangan' => 'Pendakian dini hari di pasir vulkanik.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Toya Bungkah', CheckpointType::BASECAMP, 1100],
                    ['Puncak Batur', CheckpointType::SUMMIT, 1717],
                ],
                'profil' => [['km' => 0, 'm' => 1100], ['km' => 2.3, 'm' => 1450], ['km' => 4.6, 'm' => 1717]],
            ],
            'rinjani-via-sembalun' => [
                'gunung' => 'gunung-rinjani', 'nama' => 'Rinjani via Sembalun', 'jarak' => 22.5, 'naik' => 2600, 'menit' => 1500,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['SAVANNA', 'SCREE', 'EXPOSED_RIDGE'], 'kemah' => true, 'awal' => 'Pintu Sembalun',
                'keterangan' => 'Sabana panjang lalu pasir berbatu ke puncak.',
                'status' => OfficialStatusValue::CLOSED, 'alasan' => 'Penutupan musiman',
                'pos' => [
                    ['Pintu Sembalun', CheckpointType::BASECAMP, 1150],
                    ['Plawangan Sembalun', CheckpointType::CAMPGROUND, 2639],
                    ['Puncak Rinjani', CheckpointType::SUMMIT, 3726],
                ],
                'profil' => [['km' => 0, 'm' => 1150], ['km' => 8.0, 'm' => 1800], ['km' => 15.0, 'm' => 2639], ['km' => 22.5, 'm' => 3726]],
            ],
        ];
    }
}
