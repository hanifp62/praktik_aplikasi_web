<?php

namespace Database\Seeders;

use App\Enums\SourceType;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use Illuminate\Database\Seeder;

/**
 * Aturan perizinan yang berlaku September 2026 untuk gunung berkuota.
 *
 * Ini catatan tentang aturan pihak lain, bukan sistem perizinan kita. Sistem tidak
 * pernah memesan, memverifikasi, maupun melacak kuota; ia hanya memberi tahu pendaki
 * apa yang perlu diurus dan ke mana (PRD §36).
 *
 * Yang tidak disebutkan sumbernya dibiarkan kosong. Kuota Rinjani dan kewajiban
 * pemandunya tidak diumumkan pada pemberitaan yang saya baca, dan mengisinya dengan
 * angka yang terdengar masuk akal akan membuat pendaki merencanakan berdasarkan
 * karangan.
 */
class PermitRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $sumber = DataSource::updateOrCreate(
            ['source_name' => 'Pemberitaan aturan pendakian taman nasional'],
            [
                'source_type' => SourceType::ADMIN_VERIFIED->value,
                'source_url' => 'https://www.cnnindonesia.com/gaya-hidup/20260427232308-269-1352789/gunung-semeru-kembali-dibuka-cek-syarat-dan-aturan-baru-buat-pendaki',
                'source_owner' => 'Balai Taman Nasional terkait',
                'retrieved_at' => now(),
                'verified_at' => now(),
                'freshness_policy' => 'Aturan kuota dan jendela pemesanan berubah mengikuti musim dan '
                    .'kebijakan balai. Periksa ke situs resmi sebelum merencanakan tanggal.',
                'notes' => 'Dicatat dari pemberitaan atas pengumuman balai, bukan dari kanal resmi '
                    .'balai secara langsung.',
            ]
        );

        foreach ($this->aturan() as $data) {
            $gunung = Mountain::where('slug', $data['slug'])->first();

            if ($gunung === null) {
                continue;
            }

            PermitRequirement::updateOrCreate(
                ['mountain_id' => $gunung->id, 'trail_id' => null],
                [
                    'authority' => $data['authority'],
                    'booking_url' => $data['booking_url'],
                    'daily_quota' => $data['daily_quota'],
                    'booking_opens_days_before' => $data['opens'],
                    'booking_closes_days_before' => $data['closes'],
                    'guide_required' => $data['guide'],
                    'max_duration_days' => $data['max_days'],
                    'notes' => $data['notes'],
                    'data_source_id' => $sumber->id,
                    'source' => $data['source'],
                    'source_url' => $data['source_url'],
                    'verified_at' => now(),
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aturan(): array
    {
        return [
            [
                'slug' => 'gunung-semeru',
                'authority' => 'Balai Besar TN Bromo Tengger Semeru',
                'booking_url' => 'https://bromotenggersemeru.id',
                'daily_quota' => 200,
                'opens' => 30,
                'closes' => 2,
                'guide' => true,
                'max_days' => 2,
                'notes' => 'Titik akhir pendakian dibatasi hanya sampai Ranu Kumbolo. Melanjutkan ke '
                    .'Kalimati maupun puncak Mahameru dilarang keras. Rombongan wajib didampingi '
                    .'pemandu lokal atau anggota PPGST, dengan pengecualian bersyarat bagi Mapala. '
                    .'Durasi maksimal dua hari satu malam. Pendaftaran H-30 sampai H-2.',
                'source' => 'CNN Indonesia',
                'source_url' => 'https://www.cnnindonesia.com/gaya-hidup/20260427232308-269-1352789/gunung-semeru-kembali-dibuka-cek-syarat-dan-aturan-baru-buat-pendaki',
            ],
            [
                // Kuota dan kewajiban pemandu tidak diumumkan pada pemberitaan yang dibaca,
                // jadi dibiarkan kosong. Yang penting justru terbaca di catatan: pintu
                // pemesanannya sedang tertutup.
                'slug' => 'gunung-rinjani',
                'authority' => 'Balai TN Gunung Rinjani, Nusa Tenggara Barat',
                'booking_url' => null,
                'daily_quota' => null,
                'opens' => null,
                'closes' => null,
                'guide' => false,
                'max_days' => null,
                'notes' => 'Dibuka terbatas mulai 1 September 2026 berdasarkan Surat Edaran Direktur '
                    .'Jenderal KSDAE. Layanan pemesanan daring lewat aplikasi eRinjani DIHENTIKAN '
                    .'SEMENTARA sejak 1 September 2026 dan akan dibuka kembali setelah risiko '
                    .'kebakaran dinyatakan aman. Pemesanan yang sudah dibayar sebelum tanggal itu '
                    .'tetap dilayani. Kuota harian dan kewajiban pemandu tidak diumumkan.',
                'source' => 'detikTravel',
                'source_url' => 'https://travel.detik.com/travel-news/d-8644059/pendakian-gunung-rinjani-dibuka-lagi-tapi-masih-terbatas',
            ],
        ];
    }
}
