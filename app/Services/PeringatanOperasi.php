<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengirim peringatan terstruktur ke log dan webhook opsional saat tugas terjadwal gagal
 * atau status resmi kawasan telah melewati masa berlaku (PRD §98, §99).
 */
class PeringatanOperasi
{
    /**
     * @param  array<string, mixed>  $konteks
     */
    public function tugasGagal(string $tugas, string $sebab, array $konteks = []): void
    {
        Log::error('Peringatan operasi: tugas terjadwal gagal.', [
            'tugas' => $tugas,
            'sebab' => $sebab,
        ] + $konteks);

        $this->kirimWebhook([
            'jenis' => 'tugas_gagal',
            'tugas' => $tugas,
            'sebab' => $sebab,
            'waktu' => now()->toIso8601String(),
            'konteks' => $konteks,
        ]);
    }

    /**
     * @param  array<string, int>  $ringkasan
     */
    public function statusKedaluwarsa(array $ringkasan): void
    {
        $this->kirimWebhook([
            'jenis' => 'status_kedaluwarsa',
            'waktu' => now()->toIso8601String(),
            'konteks' => $ringkasan,
        ]);
    }

    /**
     * @param  array<string, mixed>  $muatan
     */
    private function kirimWebhook(array $muatan): void
    {
        $url = trim((string) config('hiking.alerts.failure_webhook_url'));

        if ($url === '') {
            return;
        }

        try {
            Http::timeout(5)->acceptJson()->post($url, $muatan);
        } catch (Throwable $galat) {
            Log::warning('Webhook peringatan operasi tidak terjangkau.', [
                'jenis' => $muatan['jenis'] ?? null,
                'pesan' => $galat->getMessage(),
            ]);
        }
    }
}
