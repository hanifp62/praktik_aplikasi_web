<?php

namespace App\Console\Commands;

use App\Services\DataFreshnessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pemeriksaan data basi terjadwal (PRD §98), dengan hasilnya masuk log agar dapat
 * dipantau dari luar aplikasi (§99).
 *
 * Belum ada kanal notifikasi yang terkonfigurasi, jadi log adalah tempat paling jujur
 * untuk hasil ini. Admin yang membuka aplikasi tetap melihat lencana di navigasi.
 */
class CheckDataFreshness extends Command
{
    protected $signature = 'data:freshness-check';

    protected $description = 'Memeriksa status resmi yang kedaluwarsa, akan kedaluwarsa, atau lama tidak diverifikasi';

    public function handle(DataFreshnessService $freshness): int
    {
        $tinjau = $freshness->review();

        $ringkasan = [
            'kedaluwarsa' => $tinjau['kedaluwarsa']->count(),
            'akan_kedaluwarsa' => $tinjau['segera']->count(),
            'lama_tidak_diverifikasi' => $tinjau['basi']->count(),
        ];

        foreach ($ringkasan as $label => $jumlah) {
            $this->line(sprintf('%-24s %d', str_replace('_', ' ', $label), $jumlah));
        }

        if (array_sum($ringkasan) === 0) {
            $this->info('Tidak ada status yang perlu ditinjau.');

            return self::SUCCESS;
        }

        // Status kedaluwarsa berarti jalurnya sedang berjalan tanpa status resmi, jadi
        // levelnya dinaikkan agar dapat dibedakan pemantau log dari sekadar catatan basi.
        $level = $ringkasan['kedaluwarsa'] > 0 ? 'warning' : 'info';

        Log::log($level, 'Pemeriksaan kesegaran status resmi', $ringkasan);

        return self::SUCCESS;
    }
}
