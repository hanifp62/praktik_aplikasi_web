<?php

namespace App\Console\Commands;

use App\Services\Trips\TripReminderScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Pengiriman pengingat perjalanan yang jatuh tempo (FR-24), dijadwalkan setiap jam di
 * routes/console.php.
 *
 * Aman dijalankan berulang: pengingat yang sudah terkirim tidak pernah terkirim lagi.
 * Mengembalikan kode gagal bila ada surel yang gagal dikirim, supaya halaman kesehatan
 * scheduler mencatatnya sebagai gagal, bukan sehat.
 */
class SendTripReminders extends Command
{
    protected $signature = 'trips:kirim-pengingat';

    protected $description = 'Mengirim pengingat perjalanan H-3 dan H-1 yang sudah jatuh tempo';

    public function handle(TripReminderScheduler $scheduler): int
    {
        $hasil = $scheduler->kirimYangJatuhTempo(Carbon::now());

        foreach ($hasil as $label => $jumlah) {
            $this->line(sprintf('%-10s %d', $label, $jumlah));
        }

        if ($hasil['gagal'] > 0) {
            Log::warning('Sebagian pengingat perjalanan gagal dikirim dan akan dicoba lagi', $hasil);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
