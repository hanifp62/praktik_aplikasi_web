<?php

namespace Database\Seeders;

use App\Enums\SourceType;
use App\Models\DataSource;
use App\Models\Mountain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Enam belas gunung di Jawa dan Lombok beserta koordinat dan ketinggiannya.
 *
 * Datanya berasal dari Wikidata, dengan dua koreksi hasil pemeriksaan silang ke
 * Wikipedia Indonesia. Catatan kegagalan tiap sumber ditinggalkan di sini karena
 * siapa pun yang menambah gunung berikutnya akan tergoda mengulanginya:
 *
 *   Nominatim   "Gunung Prau" mengembalikan bukit di Ponorogo, Jawa Timur.
 *   OSM peak    58 kandidat bernama "Gede"; "Pangrango" teratas bukit 110 mdpl;
 *               "Papandayan" tidak muncul sama sekali.
 *   Wikidata    koordinatnya tepat, tetapi entitas "Gunung Prau" menunjuk gunung lain
 *               di Jawa Timur, dan ketinggian Raung tercatat 3260 padahal 3344.
 *
 * Pelajarannya satu: nama gunung di Indonesia banyak yang kembar, jadi tidak ada sumber
 * tunggal yang boleh dipercaya tanpa pemeriksaan silang.
 */
class MountainSeeder extends Seeder
{
    public function run(): void
    {
        $sumber = DataSource::updateOrCreate(
            ['source_name' => 'Wikidata dan Wikipedia Indonesia'],
            [
                'source_type' => SourceType::COMMUNITY->value,
                'source_url' => 'https://www.wikidata.org',
                'source_owner' => 'Wikimedia Foundation',
                'retrieved_at' => now(),
                'verified_at' => now(),
                'freshness_policy' => 'Koordinat dan ketinggian gunung jarang berubah. '
                    .'Periksa ulang bila ada laporan pengukuran baru.',
                'notes' => 'Koordinat dari Wikidata. Ketinggian Prau dan Raung dikoreksi lewat '
                    .'Wikipedia Indonesia karena nilai Wikidata-nya keliru. Ini sumber komunitas, '
                    .'bukan data resmi pengelola jalur.',
            ]
        );

        foreach ($this->gunung() as $data) {
            $gunung = Mountain::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'province' => $data['province'],
                    'region' => $data['province'],
                    'timezone' => $data['timezone'],
                    'elevation_mdpl' => $data['ele'],
                    'data_source_id' => $sumber->id,
                    'description' => $data['description'],
                ]
            );

            $gunung->setCoordinates($data['lat'], $data['lon']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gunung(): array
    {
        $jawa = 'Asia/Jakarta';
        $nusra = 'Asia/Makassar';

        return [
            // Jawa Barat
            ['name' => 'Gunung Pangrango', 'province' => 'Jawa Barat', 'lat' => -6.76667, 'lon' => 106.95000, 'ele' => 3019, 'timezone' => $jawa,
                'description' => 'Puncak kembar Gede-Pangrango di Taman Nasional Gunung Gede Pangrango.'],
            ['name' => 'Gunung Gede', 'province' => 'Jawa Barat', 'lat' => -6.78833, 'lon' => 106.98167, 'ele' => 2958, 'timezone' => $jawa,
                'description' => 'Gunung api aktif di Taman Nasional Gunung Gede Pangrango, salah satu jalur pendakian tersibuk di Jawa Barat.'],
            ['name' => 'Gunung Papandayan', 'province' => 'Jawa Barat', 'lat' => -7.32897, 'lon' => 107.71570, 'ele' => 2665, 'timezone' => $jawa,
                'description' => 'Gunung api di Garut dengan kawah aktif dan hutan mati yang menjadi penanda jalurnya.'],
            ['name' => 'Gunung Salak', 'province' => 'Jawa Barat', 'lat' => -6.71583, 'lon' => 106.73361, 'ele' => 2211, 'timezone' => $jawa,
                'description' => 'Gunung api di perbatasan Bogor dan Sukabumi, medannya rapat dan berlumpur.'],

            // Jawa Tengah
            ['name' => 'Gunung Andong', 'province' => 'Jawa Tengah', 'lat' => -7.52852, 'lon' => 110.18365, 'ele' => 1463, 'timezone' => $jawa,
                'description' => 'Gunung pendek di Magelang, sering menjadi pendakian pertama bagi pemula.'],
            ['name' => 'Gunung Ungaran', 'province' => 'Jawa Tengah', 'lat' => -7.18000, 'lon' => 110.33000, 'ele' => 2050, 'timezone' => $jawa,
                'description' => 'Gunung api di selatan Semarang dengan beberapa jalur dan sumber air panas di kakinya.'],
            // Ketinggian dan koordinat Prau dari Wikipedia Indonesia: 2.590 mdpl,
            // 7°11′13″S 109°55′22″E. Entitas Wikidata untuk nama ini menunjuk gunung lain.
            ['name' => 'Gunung Prau', 'province' => 'Jawa Tengah', 'lat' => -7.18694, 'lon' => 109.92278, 'ele' => 2590, 'timezone' => $jawa,
                'description' => 'Gunung di Dataran Tinggi Dieng, membentang di Batang, Kendal, Temanggung, dan Wonosobo.'],
            ['name' => 'Gunung Lawu', 'province' => 'Jawa Tengah', 'lat' => -7.62500, 'lon' => 111.19167, 'ele' => 3265, 'timezone' => $jawa,
                'description' => 'Gunung di perbatasan Jawa Tengah dan Jawa Timur, jalurnya panjang dan terbuka.'],
            ['name' => 'Gunung Merbabu', 'province' => 'Jawa Tengah', 'lat' => -7.45500, 'lon' => 110.44000, 'ele' => 3142, 'timezone' => $jawa,
                'description' => 'Gunung api di Taman Nasional Gunung Merbabu, dikenal karena punggungan sabananya.'],
            ['name' => 'Gunung Slamet', 'province' => 'Jawa Tengah', 'lat' => -7.23900, 'lon' => 109.22000, 'ele' => 3428, 'timezone' => $jawa,
                'description' => 'Gunung tertinggi di Jawa Tengah, jalurnya panjang dengan sedikit sumber air.'],
            ['name' => 'Gunung Sindoro', 'province' => 'Jawa Tengah', 'lat' => -7.30111, 'lon' => 109.99667, 'ele' => 3136, 'timezone' => $jawa,
                'description' => 'Gunung api berpasangan dengan Sumbing, jalurnya menanjak terus menerus.'],
            ['name' => 'Gunung Sumbing', 'province' => 'Jawa Tengah', 'lat' => -7.38500, 'lon' => 110.07250, 'ele' => 3371, 'timezone' => $jawa,
                'description' => 'Gunung api di Temanggung dan Wonosobo, tanjakannya terkenal berat.'],

            // Jawa Timur
            ['name' => 'Gunung Semeru', 'province' => 'Jawa Timur', 'lat' => -8.10000, 'lon' => 112.91667, 'ele' => 3676, 'timezone' => $jawa,
                'description' => 'Gunung tertinggi di Pulau Jawa, di Taman Nasional Bromo Tengger Semeru. Pendakiannya berkuota dan berizin.'],
            // Ketinggian Raung dari Wikipedia Indonesia: 3.344 mdpl. Wikidata mencatat 3260.
            ['name' => 'Gunung Raung', 'province' => 'Jawa Timur', 'lat' => -8.12500, 'lon' => 114.04167, 'ele' => 3344, 'timezone' => $jawa,
                'description' => 'Gunung api berkaldera di Banyuwangi, Bondowoso, dan Jember. Jalur puncaknya menuntut kemampuan teknis.'],
            ['name' => 'Gunung Arjuno', 'province' => 'Jawa Timur', 'lat' => -7.76500, 'lon' => 112.58972, 'ele' => 3339, 'timezone' => $jawa,
                'description' => 'Gunung di Malang dan Pasuruan, sering didaki bersambung dengan Welirang.'],

            // Nusa Tenggara Barat
            ['name' => 'Gunung Rinjani', 'province' => 'Nusa Tenggara Barat', 'lat' => -8.41667, 'lon' => 116.46667, 'ele' => 3726, 'timezone' => $nusra,
                'description' => 'Gunung api di Lombok dengan Danau Segara Anak di kalderanya. Pendakiannya berizin lewat sistem resmi taman nasional.'],
        ];
    }
}
