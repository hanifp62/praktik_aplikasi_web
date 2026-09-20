<?php

namespace Database\Seeders;

use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Jalur pendakian yang namanya diketahui luas, dengan karakteristiknya sengaja kosong.
 *
 * Ini bukan data setengah jadi karena kelalaian, melainkan keadaan yang memang ingin
 * ditampilkan sistem ini: jalurnya nyata dan dikenal pendaki, tetapi datanya belum
 * dimasukkan pihak yang berwenang. Kekosongan itu yang menjadi undangan.
 *
 * Aman menurut rancangan yang sudah ada:
 *
 *   Karakteristik kosong diperlakukan CompatibilityScorer sebagai paling berat, bukan
 *   paling ringan, sehingga tidak ada jalur yang terlihat mudah karena datanya hilang.
 *   Gerbang §110 menahan publikasinya sampai sumber, karakteristik, checkpoint, status,
 *   dan geometrinya lengkap.
 *
 * Nama jalur diambil dari nama basecamp atau desa pintu masuk yang lazim dipakai
 * pendaki. Yang tidak diisi adalah angka, karena angka yang salah pada jarak dan
 * elevation gain langsung memengaruhi penilaian kecocokan.
 */
class AwaitingTrailSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->jalur() as $slugGunung => $namaJalur) {
            $gunung = Mountain::where('slug', $slugGunung)->first();

            if ($gunung === null) {
                continue;
            }

            foreach ($namaJalur as $nama) {
                Trail::updateOrCreate(
                    ['slug' => Str::slug($nama.'-'.$gunung->slug)],
                    [
                        'mountain_id' => $gunung->id,
                        'name' => $nama,

                        // Semua karakteristik dibiarkan null. Jangan diisi angka perkiraan:
                        // mesin rekomendasi memakainya untuk menilai kecocokan, dan tebakan
                        // di sini berubah menjadi saran yang salah bagi pendaki pemula.
                        'is_published' => false,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function jalur(): array
    {
        return [
            'gunung-pangrango' => ['Jalur Cibodas', 'Jalur Gunung Putri'],
            'gunung-salak' => ['Jalur Cidahu', 'Jalur Cimelati'],
            'gunung-andong' => ['Jalur Sawit', 'Jalur Pendem'],
            'gunung-ungaran' => ['Jalur Mawar', 'Jalur Promasan'],
            'gunung-lawu' => ['Jalur Cemoro Sewu', 'Jalur Cemoro Kandang', 'Jalur Candi Cetho'],
            'gunung-slamet' => ['Jalur Bambangan', 'Jalur Guci'],
            'gunung-sumbing' => ['Jalur Garung', 'Jalur Bowongso'],
            'gunung-semeru' => ['Jalur Ranu Pani'],
            'gunung-raung' => ['Jalur Kalibaru', 'Jalur Sumberwringin'],
            'gunung-arjuno' => ['Jalur Tretes', 'Jalur Purwosari'],
            'gunung-rinjani' => ['Jalur Sembalun', 'Jalur Senaru', 'Jalur Torean'],

            // Gunung yang sudah punya jalur berdata lengkap ikut ditambah jalur lain yang
            // sama dikenalnya, supaya kedua keadaan terlihat berdampingan di satu gunung.
            'gunung-merbabu' => ['Jalur Wekas', 'Jalur Thekelan'],
            'gunung-sindoro' => ['Jalur Sigedang'],
            'gunung-prau' => ['Jalur Kalilembu'],
            'gunung-papandayan' => [],
            'gunung-gede' => ['Jalur Gunung Putri', 'Jalur Salabintana'],
        ];
    }
}
