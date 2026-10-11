<?php

namespace Database\Seeders;

use App\Enums\CheckpointType;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
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
use App\Enums\VerificationStatus;
use App\Enums\WaterAvailability;
use App\Exceptions\DataContohDitolak;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data contoh berlabel (DK-35, spec v3 bagian 11).
 *
 * Satu pendaki pemula, Budi Setiawan; delapan gunung; sebelas jalur (sepuluh terbit, satu
 * menunggu data); trip "Gede via Cibodas" lima belas hari lagi; tiga pendakian selesai; satu
 * trip batal. Isinya meniru contoh di prototype pemilik supaya tangkapan pratinjau dapat
 * dibandingkan dengan gambar acuannya: tanjakan tertinggi Budi 700 m dari Prau, sehingga
 * Cibodas (1.650 m) menjadi tanjakan terbesarnya.
 *
 * Setiap teks yang dapat terbaca sebagai fakta diawali "DATA CONTOH", dan layout memasang
 * lencana "Data contoh" lewat APP_DEMO. Geometri jalur tidak diisi: kolom geografi hanya
 * ada di PostGIS, sedangkan data contoh wajib SQLite (W1-K10). Kata sandi pendaki contoh
 * diacak setiap kali dan hanya dicetak ke konsol.
 *
 * Jalan masuknya `php artisan memount:demo` (penjaga berkas di PenjagaDataContoh) atau
 * suite PHPUnit Pratinjau (SQLite dalam memori). DatabaseSeeder tidak memanggilnya.
 */
class DemoSeeder extends Seeder
{
    public const EMAIL_PENDAKI = 'budi@contoh.test';

    public const PENANDA = 'DATA CONTOH';

    /**
     * Gunung: nama, provinsi, zona waktu, ketinggian, lintang, bujur. Koordinat Gede,
     * Pangrango, Papandayan, Prau, dan Rinjani sama dengan MountainSeeder; Batu tanpa
     * koordinat.
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: int, 4: float|null, 5: float|null}>
     */
    private const GUNUNG = [
        'gede' => ['Gunung Gede', 'Jawa Barat', 'Asia/Jakarta', 2958, -6.78833, 106.98167],
        'pangrango' => ['Gunung Pangrango', 'Jawa Barat', 'Asia/Jakarta', 3019, -6.76667, 106.95000],
        'papandayan' => ['Gunung Papandayan', 'Jawa Barat', 'Asia/Jakarta', 2665, -7.32897, 107.71570],
        'batu' => ['Gunung Batu', 'Jawa Barat', 'Asia/Jakarta', 875, null, null],
        'prau' => ['Gunung Prau', 'Jawa Tengah', 'Asia/Jakarta', 2590, -7.18694, 109.92278],
        'andong' => ['Gunung Andong', 'Jawa Tengah', 'Asia/Jakarta', 1726, -7.39040, 110.37060],
        'batur' => ['Gunung Batur', 'Bali', 'Asia/Makassar', 1717, -8.24190, 115.37530],
        'rinjani' => ['Gunung Rinjani', 'Nusa Tenggara Barat', 'Asia/Makassar', 3726, -8.41667, 116.46667],
    ];

    public function run(): void
    {
        $pengemudi = DB::connection()->getDriverName();

        if ($pengemudi !== 'sqlite') {
            throw new DataContohDitolak("Data contoh hanya ditulis ke SQLite; koneksi bawaan sekarang {$pengemudi}.");
        }

        if (app()->environment('production')) {
            throw new DataContohDitolak('Data contoh tidak pernah ditulis di lingkungan produksi.');
        }

        $this->call([RecommendationRuleSeeder::class, PreparationTemplateSeeder::class]);

        $sumber = DataSource::create([
            'source_name' => self::PENANDA.', bukan sumber resmi',
            'source_type' => SourceType::ADMIN_VERIFIED->value,
            'source_owner' => 'Tim Memount',
            'retrieved_at' => now(),
            // Tinjauan W1-9a #6: null, bukan now(), supaya cocok dengan
            // verification_status UNVERIFIED di bawah. source_type ADMIN_VERIFIED tetap
            // karena itu klasifikasi otoritas sumbernya (tim internal), bukan status
            // verifikasi catatan ini sendiri.
            'verified_at' => null,
            'freshness_policy' => 'Data contoh untuk peninjauan tampilan lokal; tidak pernah dipakai di produksi.',
            'verification_status' => VerificationStatus::UNVERIFIED->value,
            'notes' => self::PENANDA.'. Nilai jalur, pos, dan status dibuat untuk peninjauan, bukan diambil dari pengelola.',
        ]);

        $budi = $this->pendaki();
        $jalur = $this->jalur($sumber);
        $this->trip($budi, $jalur);
    }

    private function pendaki(): User
    {
        $kataSandi = Str::password(20, symbols: false);

        $budi = User::create(['name' => 'Budi Setiawan', 'email' => self::EMAIL_PENDAKI, 'password' => $kataSandi]);
        $budi->forceFill(['email_verified_at' => now()])->save();

        $budi->profile()->create([
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'region_preference' => 'Jawa Barat',
            'completed_at' => now(),
        ]);

        $budi->experience()->create([
            'completed_hikes_count' => 3,
            'terrain_experience' => ['FOREST', 'ROCKY'],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'longest_hike_duration_minutes' => 300,
            'highest_elevation_gain_m' => 700,
        ]);

        $budi->preference()->create([
            'preferred_duration' => PreferredDuration::TWO_DAY->value,
            'preferred_trip_type' => TripType::CAMPING->value,
            'preferred_challenge' => PreferredChallenge::MODERATE->value,
            'region_preference' => 'Jawa Barat, Jawa Tengah',
            'record_track' => false,
        ]);

        $this->command?->info('Pendaki contoh: '.self::EMAIL_PENDAKI." dengan kata sandi {$kataSandi} (hanya untuk berkas lokal ini).");

        return $budi;
    }

    /**
     * @return array<string, Trail>
     */
    private function jalur(DataSource $sumber): array
    {
        $gunung = [];

        foreach (self::GUNUNG as $kunci => [$nama, $provinsi, $zona, $tinggi, $lintang, $bujur]) {
            $gunung[$kunci] = Mountain::create([
                'name' => $nama,
                'slug' => Str::slug($nama),
                'province' => $provinsi,
                'region' => $provinsi,
                'timezone' => $zona,
                'elevation_mdpl' => $tinggi,
                'latitude' => $lintang,
                'longitude' => $bujur,
                'description' => self::PENANDA.". {$nama} di {$provinsi}, dipakai untuk meninjau tampilan.",
                'data_source_id' => $sumber->id,
            ]);
        }

        $jalur = [];

        foreach ($this->dataJalur() as $slug => $data) {
            $trail = Trail::create([
                'mountain_id' => $gunung[$data['gunung']]->id,
                'name' => $data['nama'],
                'slug' => $slug,
                'description' => self::PENANDA.'. '.$data['keterangan'],
                'distance_km' => $data['jarak'],
                'elevation_gain_m' => $data['naik'],
                'elevation_loss_m' => $data['naik'],
                'elevation_profile' => $data['profil'] ?? null,
                'estimated_duration_minutes' => $data['menit'],
                'technical_demand' => $data['teknis']->value,
                'navigation_complexity' => $data['navigasi']->value,
                'water_availability' => $data['air']->value,
                'terrain_character' => $data['medan'],
                'camping_available' => $data['kemah'],
                'starting_point' => $data['awal'],
                'data_source_id' => $sumber->id,
            ]);

            $jalur[$slug] = $trail;

            if ($data['status'] === null) {
                continue;
            }

            foreach ($data['pos'] as $urutan => [$nama, $jenis, $tinggi]) {
                Checkpoint::create([
                    'trail_id' => $trail->id,
                    'sequence' => $urutan + 1,
                    'name' => $nama,
                    'checkpoint_type' => $jenis->value,
                    'elevation_m' => $tinggi,
                ]);
            }

            OfficialStatus::create([
                'statusable_type' => $trail->getMorphClass(),
                'statusable_id' => $trail->id,
                'scope' => StatusScope::TRAIL->value,
                'status' => $data['status']->value,
                'data_source_id' => $sumber->id,
                'source' => self::PENANDA.', bukan status resmi',
                'published_at' => now()->subDays(8),
                'fetched_at' => now()->subDays(8),
                'effective_at' => now()->subDays(8),
                'verified_at' => now()->subDays(7),
                // Tinjauan W1-9a #5: reason juga terbaca sebagai fakta ("Perbaikan
                // jembatan", "Penutupan musiman"), jadi memakai penanda yang sama dengan
                // description dan notes, bukan ditulis polos.
                'reason' => isset($data['alasan']) ? self::PENANDA.'. '.$data['alasan'] : null,
            ]);

            // Menerbitkan lewat gerbang publikasi model, bukan menulis kolomnya langsung.
            $trail->refresh()->update(['is_published' => true]);
        }

        return $jalur;
    }

    /**
     * @param  array<string, Trail>  $jalur
     */
    private function trip(User $budi, array $jalur): void
    {
        $hariIni = Carbon::parse(Timezone::earliestDateInIndonesia());

        TripPlan::create([
            'user_id' => $budi->id,
            'trail_id' => $jalur['gede-via-cibodas']->id,
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

        foreach ([['andong-via-sawit', 120, TripType::TEKTOK], ['batur-via-toya-bungkah', 75, TripType::TEKTOK], ['prau-via-wates', 40, TripType::CAMPING]] as [$slug, $hariLalu, $jenis]) {
            $tanggal = $hariIni->copy()->subDays($hariLalu);
            $selesai = $tanggal->copy()->setTime(16, 0);

            $trip = TripPlan::create([
                'user_id' => $budi->id,
                'trail_id' => $jalur[$slug]->id,
                'name' => $jalur[$slug]->name,
                'planned_date' => $tanggal->toDateString(),
                'trip_type' => $jenis->value,
                'status' => TripStatus::COMPLETED->value,
                'completed_at' => $selesai,
            ]);

            HikingHistory::create([
                'user_id' => $budi->id,
                'trip_plan_id' => $trip->id,
                'trail_id' => $jalur[$slug]->id,
                'trip_type' => $jenis->value,
                'completion_state' => CompletionState::COMPLETED->value,
                'completed_at' => $selesai,
            ]);
        }

        TripPlan::create([
            'user_id' => $budi->id,
            'trail_id' => $jalur['pangrango-via-cibodas']->id,
            'name' => 'Pangrango akhir pekan',
            'planned_date' => $hariIni->copy()->subDays(20)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'notes' => self::PENANDA.'. Dibatalkan karena cuaca.',
            'status' => TripStatus::CANCELLED->value,
        ]);
    }

    /**
     * Sebelas jalur. Jalur dengan status null belum punya pos dan status resmi, jadi tetap
     * tidak terbit dan tampil sebagai jalur yang datanya belum tersedia.
     *
     * @return array<string, array<string, mixed>>
     */
    private function dataJalur(): array
    {
        return [
            'gede-via-cibodas' => [
                'gunung' => 'gede', 'nama' => 'Gede via Cibodas', 'jarak' => 9.7, 'naik' => 1650, 'menit' => 450,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'ROCKY', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Cibodas',
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
                'gunung' => 'gede', 'nama' => 'Gede via Gunung Putri', 'jarak' => 7.2, 'naik' => 1720, 'menit' => 390,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['FOREST', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Basecamp Gunung Putri',
                'keterangan' => 'Jalur pendek yang menanjak terus sampai Alun-alun Surya Kencana.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Gunung Putri', CheckpointType::BASECAMP, 1450],
                    ['Alun-alun Surya Kencana', CheckpointType::CAMPGROUND, 2750],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
            ],
            'gede-via-selabintana' => [
                'gunung' => 'gede', 'nama' => 'Gede via Selabintana', 'jarak' => 11.8, 'naik' => 1900, 'menit' => 660,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::HIGH, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'MUD', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Selabintana',
                'keterangan' => 'Jalur terpanjang ke Gede dari sisi Sukabumi.',
                'status' => OfficialStatusValue::RESTRICTED, 'alasan' => 'Perbaikan jembatan',
                'pos' => [
                    ['Pintu Selabintana', CheckpointType::BASECAMP, 1100],
                    ['Alun-alun Surya Kencana', CheckpointType::CAMPGROUND, 2750],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
            ],
            'pangrango-via-cibodas' => [
                'gunung' => 'pangrango', 'nama' => 'Pangrango via Cibodas', 'jarak' => 11.2, 'naik' => 1680, 'menit' => 510,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Cibodas',
                'keterangan' => 'Berpisah dari jalur Gede di Kandang Badak, lalu hutan rapat sampai puncak.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Pintu Cibodas', CheckpointType::BASECAMP, 1340],
                    ['Kandang Badak', CheckpointType::CAMPGROUND, 2395],
                    ['Puncak Pangrango', CheckpointType::SUMMIT, 3019],
                ],
            ],
            'papandayan-via-camp-david' => [
                'gunung' => 'papandayan', 'nama' => 'Papandayan via Camp David', 'jarak' => 7.4, 'naik' => 610, 'menit' => 270,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::ABUNDANT,
                'medan' => ['ROCKY', 'FOREST'], 'kemah' => true, 'awal' => 'Camp David',
                'keterangan' => 'Melewati kawah lalu padang Pondok Saladah.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Camp David', CheckpointType::BASECAMP, 2050],
                    ['Pondok Saladah', CheckpointType::CAMPGROUND, 2288],
                    ['Tegal Alun', CheckpointType::POS, 2600],
                ],
            ],
            'batu-via-jonggol' => [
                'gunung' => 'batu', 'nama' => 'Batu via Jonggol', 'jarak' => 1.6, 'naik' => 280, 'menit' => 90,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['ROCKY'], 'kemah' => false, 'awal' => 'Basecamp Gunung Batu',
                'keterangan' => 'Pendakian singkat ke puncak batu di Bogor timur.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Gunung Batu', CheckpointType::BASECAMP, 600],
                    ['Puncak Gunung Batu', CheckpointType::SUMMIT, 875],
                ],
            ],
            'prau-via-wates' => [
                'gunung' => 'prau', 'nama' => 'Prau via Wates', 'jarak' => 6.1, 'naik' => 700, 'menit' => 270,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'SAVANNA'], 'kemah' => true, 'awal' => 'Basecamp Wates',
                'keterangan' => 'Jalur hutan yang lebih sepi menuju sabana Prau.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Wates', CheckpointType::BASECAMP, 1890],
                    ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                ],
            ],
            'andong-via-sawit' => [
                'gunung' => 'andong', 'nama' => 'Andong via Sawit', 'jarak' => 2.8, 'naik' => 380, 'menit' => 120,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['FOREST', 'EXPOSED_RIDGE'], 'kemah' => true, 'awal' => 'Basecamp Sawit',
                'keterangan' => 'Pendakian pendek ke punggungan terbuka.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Sawit', CheckpointType::BASECAMP, 1340],
                    ['Puncak Andong', CheckpointType::SUMMIT, 1726],
                ],
            ],
            'batur-via-toya-bungkah' => [
                'gunung' => 'batur', 'nama' => 'Batur via Toya Bungkah', 'jarak' => 4.6, 'naik' => 600, 'menit' => 150,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['SAND', 'ROCKY'], 'kemah' => false, 'awal' => 'Toya Bungkah',
                'keterangan' => 'Pendakian dini hari di pasir vulkanik.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Toya Bungkah', CheckpointType::BASECAMP, 1100],
                    ['Puncak Batur', CheckpointType::SUMMIT, 1717],
                ],
            ],
            'rinjani-via-sembalun' => [
                'gunung' => 'rinjani', 'nama' => 'Rinjani via Sembalun', 'jarak' => 22.5, 'naik' => 2600, 'menit' => 1500,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['SAVANNA', 'SCREE', 'EXPOSED_RIDGE'], 'kemah' => true, 'awal' => 'Pintu Sembalun',
                'keterangan' => 'Sabana panjang lalu pasir berbatu ke puncak.',
                'status' => OfficialStatusValue::CLOSED, 'alasan' => 'Penutupan musiman',
                'pos' => [
                    ['Pintu Sembalun', CheckpointType::BASECAMP, 1150],
                    ['Plawangan Sembalun', CheckpointType::CAMPGROUND, 2639],
                    ['Puncak Rinjani', CheckpointType::SUMMIT, 3726],
                ],
            ],
            'rinjani-via-senaru' => [
                'gunung' => 'rinjani', 'nama' => 'Rinjani via Senaru', 'jarak' => null, 'naik' => null, 'menit' => null,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::UNKNOWN,
                'medan' => null, 'kemah' => false, 'awal' => null,
                'keterangan' => 'Jalur yang dikenali sistem tetapi datanya belum dimasukkan pengelola.',
                'status' => null,
            ],
        ];
    }
}
