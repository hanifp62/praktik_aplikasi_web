<?php

namespace App\Console\Commands;

use App\Models\ReadinessCheck;
use App\Services\Readiness\ReadinessAssessment;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Membersihkan laporan komunitas di readiness_checks.condition_snapshot yang ditulis sebelum
 * snapshot diminimalkan (W4-6g, ruling W4-6h; docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2, 3, 5).
 *
 * Baris lama menyimpan laporan utuh, termasuk pelapor, catatan, dan alamat foto. Setiap laporan
 * dipangkas ke kunci yang sama dengan baris baru (ReadinessAssessment::laporanMinimal(): id,
 * created_at, hike_date, condition_tags, dan segmen {id, name}); bagian snapshot lain tidak
 * disentuh.
 *
 * - Idempoten: baris yang sudah minimal tidak ditulis ulang, jadi menjalankannya dua kali tidak
 *   mengubah apa pun.
 * - Berpotongan: baris dibaca per potongan id, bukan sekaligus.
 * - updated_at tidak berubah: waktu "Tinjauan tercatat" dibaca dari kolom itu.
 * - Keluarannya hanya hitungan, tidak pernah isi laporan.
 * - --dry-run hanya menghitung; di produksi, penulisan meminta konfirmasi (atau --force).
 */
class BersihkanSnapshotKondisi extends Command
{
    use ConfirmableTrait;

    protected $signature = 'memount:bersihkan-snapshot-kondisi
        {--dry-run : Hanya menghitung baris yang akan dibersihkan, tanpa menulis}
        {--potongan=200 : Jumlah baris per potongan}
        {--force : Menulis di produksi tanpa pertanyaan konfirmasi}';

    protected $description = 'Memangkas laporan komunitas di snapshot kesiapan lama ke kunci minimal (tanpa data pribadi pelapor)';

    public function handle(): int
    {
        $uji = (bool) $this->option('dry-run');

        if (! $uji && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $diperiksa = 0;
        $dibersihkan = 0;

        ReadinessCheck::query()
            ->whereNotNull('condition_snapshot')
            ->select(['id', 'condition_snapshot'])
            ->chunkById(max(1, (int) $this->option('potongan')), function ($potongan) use ($uji, &$diperiksa, &$dibersihkan): void {
                foreach ($potongan as $cek) {
                    $diperiksa++;
                    $snapshot = $cek->condition_snapshot;
                    $laporan = $snapshot['community']['reports'] ?? null;

                    if (! is_array($laporan)) {
                        continue;
                    }

                    $minimal = array_map(ReadinessAssessment::laporanMinimal(...), $laporan);

                    if ($minimal === $laporan) {
                        continue;
                    }

                    $dibersihkan++;

                    if (! $uji) {
                        $snapshot['community']['reports'] = $minimal;
                        $cek->timestamps = false;
                        $cek->forceFill(['condition_snapshot' => $snapshot])->saveQuietly();
                    }
                }
            });

        $this->info(sprintf(
            '%d baris snapshot diperiksa; %d baris %s.',
            $diperiksa,
            $dibersihkan,
            $uji ? 'akan dibersihkan (uji coba, tidak ada yang ditulis)' : 'dibersihkan',
        ));

        return self::SUCCESS;
    }
}
