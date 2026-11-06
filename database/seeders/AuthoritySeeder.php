<?php

namespace Database\Seeders;

use App\Enums\AuthorityType;
use App\Models\Authority;
use App\Models\Mountain;
use Illuminate\Database\Seeder;

/**
 * Badan resmi yang benar-benar ada, beserta gunung yang berada di bawahnya.
 *
 * Tiga lapis yang berbeda kewenangannya:
 *
 *   BNSP menetapkan standar kompetensi nasional.
 *   LSP menguji, APGI menaungi profesinya dan menyelenggarakan sertifikasi.
 *   Balai taman nasional berwenang atas kawasan dan status jalurnya.
 *
 * Yang pertama dan kedua mengesahkan orang; yang ketiga mengesahkan keadaan jalur.
 * Keduanya resmi, untuk hal yang berlainan (PRD §43).
 */
class AuthoritySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->badan() as $data) {
            $authority = Authority::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'type' => $data['type']->value,
                    'abbreviation' => $data['abbreviation'] ?? null,
                    'website' => $data['website'] ?? null,
                    'jurisdiction' => $data['jurisdiction'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'verified_at' => now(),
                ]
            );

            $gunung = Mountain::whereIn('slug', $data['mountains'] ?? [])->pluck('id');

            $authority->mountains()->sync($gunung);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function badan(): array
    {
        return [
            [
                'slug' => 'bnsp',
                'name' => 'Badan Nasional Sertifikasi Profesi',
                'abbreviation' => 'BNSP',
                'type' => AuthorityType::CERTIFICATION_BODY,
                'website' => 'https://bnsp.go.id',
                'jurisdiction' => 'Nasional',
                'notes' => 'Menetapkan standar dan menerbitkan sertifikat kompetensi kerja. '
                    .'Sertifikat pemandu wisata gunung berlaku tiga tahun dan dapat diperpanjang '
                    .'lewat sertifikasi ulang.',
            ],
            [
                'slug' => 'apgi',
                'name' => 'Asosiasi Pemandu Gunung Indonesia',
                'abbreviation' => 'APGI',
                'type' => AuthorityType::PROFESSIONAL_ASSOCIATION,
                'website' => 'https://www.apgi.or.id',
                'jurisdiction' => 'Nasional',
                'notes' => 'Menaungi profesi pemandu gunung dan menyelenggarakan bimbingan teknis '
                    .'serta sertifikasi bersama LSP. Jenjangnya Muda, Madya, dan Ahli menurut SKKNI '
                    .'Pemandu Wisata Gunung.',
            ],
            [
                'slug' => 'ksdae',
                'name' => 'Direktorat Jenderal Konservasi Sumber Daya Alam dan Ekosistem',
                'abbreviation' => 'KSDAE',
                'type' => AuthorityType::GOVERNMENT,
                'website' => 'https://ksdae.menlhk.go.id',
                'jurisdiction' => 'Nasional',
                'notes' => 'Menerbitkan surat edaran yang menjadi dasar pembukaan dan penutupan '
                    .'pendakian di taman nasional.',
            ],

            [
                'slug' => 'btn-gede-pangrango',
                'name' => 'Balai Besar Taman Nasional Gunung Gede Pangrango',
                'abbreviation' => 'BBTNGGP',
                'type' => AuthorityType::NATIONAL_PARK,
                'jurisdiction' => 'Jawa Barat',
                'mountains' => ['gunung-gede', 'gunung-pangrango'],
            ],
            [
                'slug' => 'btn-merbabu',
                'name' => 'Balai Taman Nasional Gunung Merbabu',
                'type' => AuthorityType::NATIONAL_PARK,
                'jurisdiction' => 'Jawa Tengah',
                'mountains' => ['gunung-merbabu'],
            ],
            [
                'slug' => 'btn-bromo-tengger-semeru',
                'name' => 'Balai Besar Taman Nasional Bromo Tengger Semeru',
                'abbreviation' => 'BBTNBTS',
                'type' => AuthorityType::NATIONAL_PARK,
                'website' => 'https://bromotenggersemeru.id',
                'jurisdiction' => 'Jawa Timur',
                'mountains' => ['gunung-semeru'],
            ],
            [
                'slug' => 'btn-rinjani',
                'name' => 'Balai Taman Nasional Gunung Rinjani',
                'abbreviation' => 'BTNGR',
                'type' => AuthorityType::NATIONAL_PARK,
                'website' => 'https://www.rinjaninationalpark.id',
                'jurisdiction' => 'Nusa Tenggara Barat',
                'mountains' => ['gunung-rinjani'],
                'notes' => 'Menyelenggarakan bimbingan teknis dan sertifikasi pemandu wisata gunung '
                    .'untuk kawasannya.',
            ],

            // Gunung di luar kawasan taman nasional umumnya dikelola Perhutani bersama
            // pengelola basecamp setempat. Dicatat sebagai satu badan sampai ada rincian
            // per gunung yang dapat diverifikasi.
            [
                'slug' => 'pengelola-basecamp-jawa',
                'name' => 'Pengelola basecamp dan Perhutani setempat',
                'type' => AuthorityType::BASECAMP,
                'jurisdiction' => 'Jawa',
                'notes' => 'Berwenang atas jalur dan basecamp di gunung yang tidak berada dalam '
                    .'kawasan taman nasional. Rincian per gunung belum diverifikasi.',
                'mountains' => [
                    'gunung-prau', 'gunung-sindoro', 'gunung-sumbing', 'gunung-lawu',
                    'gunung-andong', 'gunung-ungaran', 'gunung-slamet', 'gunung-salak',
                    'gunung-papandayan', 'gunung-arjuno', 'gunung-raung',
                ],
            ],
        ];
    }
}
