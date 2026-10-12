<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Satu kali jalannya sebuah tugas terjadwal.
 */
#[Fillable(['task', 'status', 'runtime_ms', 'summary', 'ran_at'])]
class ScheduledTaskRun extends Model
{
    public const SUCCESS = 'SUCCESS';

    public const FAILED = 'FAILED';

    /**
     * Mencatat satu jalan tugas terjadwal. Dipakai listener scheduler (server dengan schedule:work) dan
     * CronController (Vercel Cron), supaya halaman kesehatan membaca bentuk baris yang sama dari keduanya.
     */
    public static function catat(string $tugas, string $status, ?int $runtimeMs, ?string $summary): self
    {
        return self::create([
            'task' => $tugas,
            'status' => $status,
            'runtime_ms' => $runtimeMs,
            'summary' => $summary,
            'ran_at' => now(),
        ]);
    }

    /**
     * Perintah yang selesai dengan kode keluar. Kode bukan nol berarti perintahnya berjalan tetapi mengaku
     * gagal; tanpa pemeriksaan ini, tugas yang gagal setiap hari akan tercatat sehat setiap hari.
     */
    public static function catatKeluar(string $tugas, int $kodeKeluar, ?int $runtimeMs): self
    {
        return $kodeKeluar === 0
            ? self::catat($tugas, self::SUCCESS, $runtimeMs, null)
            : self::catat($tugas, self::FAILED, $runtimeMs, 'Keluar dengan kode '.$kodeKeluar.'.');
    }

    /**
     * Perintah yang berhenti karena galat, beserta pesannya.
     */
    public static function catatGalat(string $tugas, Throwable $galat, ?int $runtimeMs = null): self
    {
        return self::catat($tugas, self::FAILED, $runtimeMs, mb_substr($galat->getMessage(), 0, 500));
    }

    protected function casts(): array
    {
        return [
            'ran_at' => 'datetime',
        ];
    }

    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->where('status', self::SUCCESS);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS;
    }
}
