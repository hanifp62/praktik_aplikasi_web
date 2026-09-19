<?php

namespace Database\Seeders;

use App\Enums\CheckpointType;
use App\Enums\NavigationComplexity;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\TechnicalDemand;
use App\Enums\VerificationStatus;
use App\Enums\WaterAvailability;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Curated demo dataset (PRD §108): a handful of mountains to exercise the product logic,
 * not a catalogue of every Indonesian mountain.
 *
 * IMPORTANT: this is placeholder data entered for development. Official status is seeded as
 * UNKNOWN because no live official feed is integrated yet - BR-07 prefers UNKNOWN over an
 * assumed OPEN.
 */
class MvpDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $source = DataSource::updateOrCreate(
            ['source_name' => 'Data kurasi internal (demo)'],
            [
                'source_type' => SourceType::ADMIN_VERIFIED->value,
                'source_owner' => 'Tim pengembang',
                'retrieved_at' => now(),
                'verified_at' => now(),
                'freshness_policy' => 'Data contoh untuk pengembangan, perlu diganti data terverifikasi pengelola',
                'verification_status' => VerificationStatus::UNVERIFIED->value,
                'notes' => 'Dataset contoh untuk menguji alur produk. Bukan sumber resmi.',
            ]
        );

        foreach ($this->dataset() as $entry) {
            $mountain = Mountain::updateOrCreate(
                ['slug' => Str::slug($entry['name'])],
                [
                    'name' => $entry['name'],
                    'province' => $entry['province'],
                    'region' => $entry['region'],
                    'elevation_mdpl' => $entry['elevation_mdpl'],
                    'description' => $entry['description'],
                    'data_source_id' => $source->id,
                ]
            );

            $mountain->writePoint('location', $entry['lat'], $entry['lng']);

            if (isset($entry['permit'])) {
                PermitRequirement::updateOrCreate(
                    ['mountain_id' => $mountain->id, 'trail_id' => null],
                    array_merge($entry['permit'], [
                        'data_source_id' => $source->id,
                        // Disalin saat pengembangan dan belum diperiksa ke pengelola,
                        // jadi dibiarkan belum terverifikasi (PRD §60).
                        'verified_at' => null,
                    ])
                );
            }

            foreach ($entry['trails'] as $trailData) {
                $trail = Trail::updateOrCreate(
                    ['slug' => Str::slug($entry['name'].'-'.$trailData['name'])],
                    [
                        'mountain_id' => $mountain->id,
                        'name' => $trailData['name'],
                        'description' => $trailData['description'],
                        'distance_km' => $trailData['distance_km'],
                        'elevation_gain_m' => $trailData['elevation_gain_m'],
                        'elevation_loss_m' => $trailData['elevation_gain_m'],
                        'estimated_duration_minutes' => $trailData['duration'],
                        'technical_demand' => $trailData['technical']->value,
                        'navigation_complexity' => $trailData['navigation']->value,
                        'water_availability' => $trailData['water']->value,
                        'terrain_character' => $trailData['terrain'],
                        'camping_available' => $trailData['camping'],
                        'starting_point' => $trailData['starting_point'],
                        'weather_adm4_code' => $trailData['adm4'],
                        'weather_reference_area' => $trailData['weather_area'],
                        'data_source_id' => $source->id,
                        'is_published' => true,
                    ]
                );

                foreach ($trailData['checkpoints'] as $index => $checkpoint) {
                    Checkpoint::updateOrCreate(
                        ['trail_id' => $trail->id, 'sequence' => $index + 1],
                        [
                            'name' => $checkpoint[0],
                            'checkpoint_type' => $checkpoint[1]->value,
                            'elevation_m' => $checkpoint[2],
                        ]
                    );
                }

                // No live official feed yet: record UNKNOWN rather than assuming the trail is open.
                OfficialStatus::updateOrCreate(
                    [
                        'statusable_type' => $trail->getMorphClass(),
                        'statusable_id' => $trail->id,
                        'scope' => StatusScope::TRAIL->value,
                    ],
                    [
                        'status' => OfficialStatusValue::UNKNOWN->value,
                        'data_source_id' => $source->id,
                        'source' => 'Belum terhubung ke sumber resmi',
                        'fetched_at' => now(),
                        'effective_at' => now(),
                        'notes' => 'Status perlu dikonfirmasi ke pengelola jalur sebelum dipakai sebagai acuan.',
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dataset(): array
    {
        return [
            [
                'name' => 'Gunung Prau',
                'province' => 'Jawa Tengah',
                'region' => 'Jawa Tengah',
                'elevation_mdpl' => 2590,
                'description' => 'Gunung dengan padang sabana luas dan jalur relatif pendek.',
                'lat' => -7.1936,
                'lng' => 109.9265,
                'trails' => [
                    [
                        'name' => 'Jalur Patak Banteng',
                        'description' => 'Jalur populer dengan tangga beton dan tanjakan konsisten.',
                        'distance_km' => 4.2,
                        'elevation_gain_m' => 560,
                        'duration' => 240,
                        'technical' => TechnicalDemand::LOW,
                        'navigation' => NavigationComplexity::LOW,
                        'water' => WaterAvailability::LIMITED,
                        'terrain' => ['FOREST', 'SAVANNA', 'STEEP_SLOPE'],
                        'camping' => true,
                        'starting_point' => 'Basecamp Patak Banteng',
                        'adm4' => '33.07.15.2003',
                        'weather_area' => 'Kejajar, Wonosobo',
                        'checkpoints' => [
                            ['Basecamp Patak Banteng', CheckpointType::BASECAMP, 1900],
                            ['Pos 1', CheckpointType::POS, 2050],
                            ['Pos 2', CheckpointType::POS, 2250],
                            ['Pos 3', CheckpointType::POS, 2400],
                            ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                        ],
                    ],
                    [
                        'name' => 'Jalur Dieng',
                        'description' => 'Jalur landai dengan jarak lebih panjang.',
                        'distance_km' => 6.5,
                        'elevation_gain_m' => 480,
                        'duration' => 300,
                        'technical' => TechnicalDemand::LOW,
                        'navigation' => NavigationComplexity::MODERATE,
                        'water' => WaterAvailability::LIMITED,
                        'terrain' => ['FOREST', 'SAVANNA'],
                        'camping' => true,
                        'starting_point' => 'Basecamp Dieng',
                        'adm4' => '33.07.15.2001',
                        'weather_area' => 'Dieng, Wonosobo',
                        'checkpoints' => [
                            ['Basecamp Dieng', CheckpointType::BASECAMP, 2090],
                            ['Pos Bayangan', CheckpointType::POS, 2250],
                            ['Sabana', CheckpointType::CAMPGROUND, 2480],
                            ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Gunung Merbabu',
                'permit' => [
                    'authority' => 'Balai TN Gunung Merbabu',
                    'booking_url' => null,
                    'daily_quota' => null,
                    'booking_opens_days_before' => null,
                    'booking_closes_days_before' => null,
                    'guide_required' => false,
                    'max_duration_days' => null,
                    'notes' => 'Pendaftaran dilakukan di basecamp masing-masing jalur. Ketentuan kuota mengikuti pengumuman pengelola.',
                    'source' => 'Informasi basecamp (disalin saat pengembangan, belum diverifikasi)',
                    'source_url' => null,
                ],
                'province' => 'Jawa Tengah',
                'region' => 'Jawa Tengah',
                'elevation_mdpl' => 3145,
                'description' => 'Gunung dengan punggungan terbuka dan jalur bervariasi.',
                'lat' => -7.4549,
                'lng' => 110.4406,
                'trails' => [
                    [
                        'name' => 'Jalur Selo',
                        'description' => 'Jalur panjang dengan punggungan terbuka dan angin kencang.',
                        'distance_km' => 12.0,
                        'elevation_gain_m' => 1300,
                        'duration' => 600,
                        'technical' => TechnicalDemand::MODERATE,
                        'navigation' => NavigationComplexity::MODERATE,
                        'water' => WaterAvailability::SCARCE,
                        'terrain' => ['FOREST', 'SAVANNA', 'EXPOSED_RIDGE', 'STEEP_SLOPE'],
                        'camping' => true,
                        'starting_point' => 'Basecamp Selo',
                        'adm4' => '33.09.14.2005',
                        'weather_area' => 'Selo, Boyolali',
                        'checkpoints' => [
                            ['Basecamp Selo', CheckpointType::BASECAMP, 1850],
                            ['Pos 1 Dok Malang', CheckpointType::POS, 2100],
                            ['Pos 2 Pandean', CheckpointType::POS, 2350],
                            ['Pos 3 Batu Tulis', CheckpointType::POS, 2600],
                            ['Sabana 1', CheckpointType::CAMPGROUND, 2800],
                            ['Puncak Kenteng Songo', CheckpointType::SUMMIT, 3145],
                        ],
                    ],
                    [
                        'name' => 'Jalur Suwanting',
                        'description' => 'Jalur dengan tanjakan curam dan sumber air di beberapa titik.',
                        'distance_km' => 10.5,
                        'elevation_gain_m' => 1550,
                        'duration' => 660,
                        'technical' => TechnicalDemand::HIGH,
                        'navigation' => NavigationComplexity::MODERATE,
                        'water' => WaterAvailability::LIMITED,
                        'terrain' => ['FOREST', 'STEEP_SLOPE', 'ROCKY'],
                        'camping' => true,
                        'starting_point' => 'Basecamp Suwanting',
                        'adm4' => '33.08.10.2001',
                        'weather_area' => 'Sawangan, Magelang',
                        'checkpoints' => [
                            ['Basecamp Suwanting', CheckpointType::BASECAMP, 1500],
                            ['Pos 1', CheckpointType::POS, 1800],
                            ['Pos 2', CheckpointType::POS, 2150],
                            ['Sabana 1', CheckpointType::CAMPGROUND, 2600],
                            ['Puncak Kenteng Songo', CheckpointType::SUMMIT, 3145],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Gunung Papandayan',
                'province' => 'Jawa Barat',
                'region' => 'Jawa Barat',
                'elevation_mdpl' => 2665,
                'description' => 'Gunung dengan kawah aktif dan area camping luas.',
                'lat' => -7.3200,
                'lng' => 107.7300,
                'trails' => [
                    [
                        'name' => 'Jalur Cisurupan',
                        'description' => 'Jalur utama melewati area kawah menuju Pondok Saladah.',
                        'distance_km' => 7.0,
                        'elevation_gain_m' => 600,
                        'duration' => 300,
                        'technical' => TechnicalDemand::LOW,
                        'navigation' => NavigationComplexity::LOW,
                        'water' => WaterAvailability::ABUNDANT,
                        'terrain' => ['ROCKY', 'FOREST'],
                        'camping' => true,
                        'starting_point' => 'Parkiran Cisurupan',
                        'adm4' => '32.05.13.2001',
                        'weather_area' => 'Cisurupan, Garut',
                        'checkpoints' => [
                            ['Parkiran Cisurupan', CheckpointType::BASECAMP, 2000],
                            ['Kawah Papandayan', CheckpointType::DANGER_POINT, 2200],
                            ['Pondok Saladah', CheckpointType::CAMPGROUND, 2300],
                            ['Tegal Alun', CheckpointType::SUMMIT, 2600],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Gunung Gede',
                'permit' => [
                    'authority' => 'Balai Besar TN Gunung Gede Pangrango',
                    'booking_url' => 'https://booking.gedepangrango.org',
                    'daily_quota' => 600,
                    'booking_opens_days_before' => 30,
                    'booking_closes_days_before' => 1,
                    'guide_required' => false,
                    'max_duration_days' => 2,
                    'notes' => 'Pendakian ditutup setiap awal tahun untuk pemulihan ekosistem. Periksa pengumuman resmi.',
                    'source' => 'Situs resmi pengelola (disalin saat pengembangan, belum diverifikasi)',
                    'source_url' => 'https://gedepangrango.org',
                ],
                'province' => 'Jawa Barat',
                'region' => 'Jawa Barat',
                'elevation_mdpl' => 2958,
                'description' => 'Gunung di kawasan taman nasional dengan sistem kuota pendakian.',
                'lat' => -6.7870,
                'lng' => 106.9800,
                'trails' => [
                    [
                        'name' => 'Jalur Cibodas',
                        'description' => 'Jalur panjang melewati air terjun dan alun-alun Suryakencana.',
                        'distance_km' => 14.0,
                        'elevation_gain_m' => 1500,
                        'duration' => 720,
                        'technical' => TechnicalDemand::MODERATE,
                        'navigation' => NavigationComplexity::MODERATE,
                        'water' => WaterAvailability::ABUNDANT,
                        'terrain' => ['FOREST', 'ROCKY', 'RIVER_CROSSING', 'STEEP_SLOPE'],
                        'camping' => true,
                        'starting_point' => 'Pintu Cibodas',
                        'adm4' => '32.03.06.2002',
                        'weather_area' => 'Cipanas, Cianjur',
                        'checkpoints' => [
                            ['Pintu Cibodas', CheckpointType::BASECAMP, 1400],
                            ['Telaga Biru', CheckpointType::JUNCTION, 1600],
                            ['Air Terjun Cibeureum', CheckpointType::WATER_SOURCE, 1650],
                            ['Kandang Badak', CheckpointType::CAMPGROUND, 2400],
                            ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Gunung Sindoro',
                'province' => 'Jawa Tengah',
                'region' => 'Jawa Tengah',
                'elevation_mdpl' => 3153,
                'description' => 'Gunung dengan jalur terbuka dan sumber air terbatas.',
                'lat' => -7.3000,
                'lng' => 109.9920,
                'trails' => [
                    [
                        'name' => 'Jalur Kledung',
                        'description' => 'Jalur utama dengan tanjakan panjang dan area terbuka.',
                        'distance_km' => 11.0,
                        'elevation_gain_m' => 1600,
                        'duration' => 660,
                        'technical' => TechnicalDemand::HIGH,
                        'navigation' => NavigationComplexity::MODERATE,
                        'water' => WaterAvailability::SCARCE,
                        'terrain' => ['FOREST', 'SAND', 'STEEP_SLOPE', 'EXPOSED_RIDGE'],
                        'camping' => true,
                        'starting_point' => 'Basecamp Kledung',
                        'adm4' => '33.23.12.2004',
                        'weather_area' => 'Kledung, Temanggung',
                        'checkpoints' => [
                            ['Basecamp Kledung', CheckpointType::BASECAMP, 1500],
                            ['Pos 1', CheckpointType::POS, 1800],
                            ['Pos 2', CheckpointType::POS, 2100],
                            ['Pos 3', CheckpointType::CAMPGROUND, 2530],
                            ['Puncak Sindoro', CheckpointType::SUMMIT, 3153],
                        ],
                    ],
                ],
            ],
        ];
    }
}
