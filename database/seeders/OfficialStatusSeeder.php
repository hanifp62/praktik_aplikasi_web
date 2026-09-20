<?php

namespace Database\Seeders;

use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Status resmi yang benar-benar berlaku pada September 2026, bukan status contoh.
 *
 * Tiga hal yang membuat isian ini berbeda dari data karangan:
 *
 *   Setiap baris membawa sumber dan tanggal terbitnya, sehingga dapat diperiksa ulang.
 *   Gunung yang tidak ditemukan keterangannya SENGAJA dibiarkan tanpa catatan, bukan
 *   ditebak sebagai terbuka. §95 menuntut UNKNOWN, dan diam adalah cara sistem ini
 *   mengatakan tidak tahu.
 *   Sumbernya adalah pemberitaan atas pengumuman pengelola, bukan pengumuman itu
 *   sendiri, jadi jenisnya ADMIN_VERIFIED, bukan OFFICIAL.
 *
 * Status berbatas waktu. Setelah masa berlakunya lewat, panel "Perlu ditinjau" akan
 * menampilkannya dan sistem kembali menjawab UNKNOWN, sesuai §95.
 */
class OfficialStatusSeeder extends Seeder
{
    public function run(): void
    {
        $sumber = DataSource::updateOrCreate(
            ['source_name' => 'Pemberitaan pengumuman pengelola taman nasional'],
            [
                'source_type' => SourceType::ADMIN_VERIFIED->value,
                'source_url' => 'https://mounture.com/berita/9-taman-nasional-tutup-pendakian-mulai-september-2026-ini-aturan-terbarunya/',
                'source_owner' => 'Kementerian Kehutanan dan Balai Taman Nasional terkait',
                'retrieved_at' => now(),
                'verified_at' => now(),
                'freshness_policy' => 'Status pendakian berubah mengikuti musim, cuaca, dan risiko '
                    .'kebakaran. Periksa ulang ke pengelola sebelum setiap keberangkatan.',
                'notes' => 'Dicatat dari pemberitaan atas pengumuman pengelola, bukan dari kanal resmi '
                    .'pengelola secara langsung. Keterangan basecamp tetap lebih menentukan di lapangan.',
            ]
        );

        foreach ($this->status() as $data) {
            $gunung = Mountain::where('slug', $data['slug'])->first();

            if ($gunung === null) {
                continue;
            }

            OfficialStatus::updateOrCreate(
                [
                    'statusable_type' => $gunung->getMorphClass(),
                    'statusable_id' => $gunung->id,
                    // Dinormalkan ke Carbon: mencocokkan string '2026-09-01' terhadap
                    // kolom datetime tidak pernah kena, dan jalan kedua akan menggandakan
                    // seluruh barisnya tanpa suara.
                    'effective_at' => Carbon::parse($data['effective_at'])->startOfDay(),
                ],
                [
                    'scope' => StatusScope::MOUNTAIN->value,
                    'status' => $data['status']->value,
                    'data_source_id' => $sumber->id,
                    'source' => $data['source'],
                    'source_url' => $data['source_url'],
                    'published_at' => $data['published_at'],
                    'fetched_at' => now(),
                    'verified_at' => now(),
                    'expires_at' => $data['expires_at'],
                    'reason' => $data['reason'],
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function status(): array
    {
        return [
            [
                'slug' => 'gunung-gede',
                'status' => OfficialStatusValue::CLOSED,
                'reason' => 'Penutupan pendakian Taman Nasional Gunung Gede Pangrango diperpanjang.',
                'source' => 'Kompas Bandung',
                'source_url' => 'https://bandung.kompas.com/read/2026/08/13/081209078/belum-kondusif-penutupan-pendakian-gunung-gede-pangrango-diperpanjang',
                'published_at' => '2026-08-13',
                'effective_at' => '2026-08-13',
                'expires_at' => '2026-10-31',
            ],
            [
                'slug' => 'gunung-pangrango',
                'status' => OfficialStatusValue::CLOSED,
                'reason' => 'Berada dalam kawasan Taman Nasional Gunung Gede Pangrango yang pendakiannya ditutup.',
                'source' => 'Kompas Bandung',
                'source_url' => 'https://bandung.kompas.com/read/2026/08/13/081209078/belum-kondusif-penutupan-pendakian-gunung-gede-pangrango-diperpanjang',
                'published_at' => '2026-08-13',
                'effective_at' => '2026-08-13',
                'expires_at' => '2026-10-31',
            ],
            [
                'slug' => 'gunung-semeru',
                'status' => OfficialStatusValue::CLOSED,
                'reason' => 'Ditutup sementara karena tingkat kerentanan kebakaran hutan dan lahan yang tinggi.',
                'source' => 'Mounture, atas pengumuman Kementerian Kehutanan',
                'source_url' => 'https://mounture.com/berita/9-taman-nasional-tutup-pendakian-mulai-september-2026-ini-aturan-terbarunya/',
                'published_at' => '2026-08-31',
                'effective_at' => '2026-09-01',
                'expires_at' => '2026-10-31',
            ],
            [
                // Dibuka terbatas, bukan dibuka penuh: pengawasan diperketat dan hanya
                // zona yang dinilai aman. RESTRICTED adalah kata yang tepat untuk itu.
                'slug' => 'gunung-merbabu',
                'status' => OfficialStatusValue::RESTRICTED,
                'reason' => 'Dibuka terbatas dengan pengawasan lebih ketat. Jalur Thekelan ditutup '
                    .'1 sampai 30 September 2026 dan Jalur Wekas ditutup 5 September 2026.',
                'source' => 'Mounture',
                'source_url' => 'https://mounture.com/berita/gunung-merbabu-tutup-2-jalur-pendakian-pada-september-2026-ini-jadwalnya/',
                'published_at' => '2026-08-31',
                'effective_at' => '2026-09-01',
                'expires_at' => '2026-09-30',
            ],
            [
                'slug' => 'gunung-rinjani',
                'status' => OfficialStatusValue::RESTRICTED,
                'reason' => 'Dibuka terbatas dengan pengawasan lebih ketat dan pembatasan pada zona '
                    .'yang dinilai aman.',
                'source' => 'Mounture, atas pengumuman Kementerian Kehutanan',
                'source_url' => 'https://mounture.com/berita/9-taman-nasional-tutup-pendakian-mulai-september-2026-ini-aturan-terbarunya/',
                'published_at' => '2026-08-31',
                'effective_at' => '2026-09-01',
                'expires_at' => '2026-10-31',
            ],

            // Prau, Sindoro, Sumbing, Lawu, Arjuno, Salak, Andong, Ungaran, Slamet, Raung,
            // dan Papandayan sengaja tidak dicatat. Tidak ada keterangan yang ditemukan,
            // dan menebaknya sebagai terbuka adalah tepat yang dilarang §95.
        ];
    }
}
