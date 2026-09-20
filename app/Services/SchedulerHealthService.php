<?php

namespace App\Services;

use App\Models\ScheduledTaskRun;
use Illuminate\Support\Carbon;

/**
 * Menilai apakah tugas terjadwal benar-benar berjalan.
 *
 * Kegagalan yang dijaga di sini bentuknya ketiadaan, bukan galat. Cron yang tidak pernah
 * dipasang, kontainer yang naik tanpa scheduler, atau server yang berhenti menjalankan
 * satu tugas tidak menghasilkan apa pun untuk dilihat. Prakiraan cuaca berhenti
 * diperbarui dan status resmi yang kedaluwarsa berhenti diperiksa, keduanya diam-diam,
 * dan halaman mana pun tetap tampak normal karena data lamanya masih ada di sana.
 *
 * Karena itu yang dinilai bukan "adakah galat" melainkan "kapan terakhir berhasil".
 */
class SchedulerHealthService
{
    public const SEHAT = 'SEHAT';

    public const TERLAMBAT = 'TERLAMBAT';

    public const GAGAL = 'GAGAL';

    public const BELUM_PERNAH = 'BELUM_PERNAH';

    /**
     * Tugas terjadwal beserta jarak maksimal antar-jalan yang masih wajar.
     *
     * Toleransinya sengaja lebih longgar dari jadwalnya. weather:refresh berjalan pukul
     * 6 dan 18 sehingga jarak normalnya 12 jam, tetapi antrean yang padat atau server
     * yang sibuk dapat menggesernya sedikit. Ambang yang dipasang persis 12 jam akan
     * menyala merah setiap beberapa hari tanpa ada yang rusak, dan penanda yang sering
     * menyala palsu berhenti dibaca.
     *
     * @var array<string, array{judul: string, jadwal: string, toleransi_jam: int}>
     */
    public const TUGAS = [
        'weather:refresh' => [
            'judul' => 'Pembaruan prakiraan BMKG',
            'jadwal' => 'Dua kali sehari, pukul 06:00 dan 18:00 WIB',
            'toleransi_jam' => 14,
        ],
        'data:freshness-check' => [
            'judul' => 'Pemeriksaan kesegaran status resmi',
            'jadwal' => 'Setiap hari pukul 07:00 WIB',
            'toleransi_jam' => 26,
        ],
    ];

    /**
     * Keadaan setiap tugas terjadwal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function report(): array
    {
        $laporan = [];

        foreach (self::TUGAS as $kunci => $tugas) {
            $terakhir = ScheduledTaskRun::query()->where('task', $kunci)->latest('ran_at')->first();
            $berhasilTerakhir = $terakhir?->isSuccess()
                ? $terakhir
                : ScheduledTaskRun::query()->where('task', $kunci)->succeeded()->latest('ran_at')->first();

            $laporan[] = [
                'kunci' => $kunci,
                'judul' => $tugas['judul'],
                'jadwal' => $tugas['jadwal'],
                'toleransi_jam' => $tugas['toleransi_jam'],
                'terakhir_jalan' => $terakhir?->ran_at,
                'terakhir_berhasil' => $berhasilTerakhir?->ran_at,
                'ringkasan' => $terakhir?->summary,
                'keadaan' => $this->keadaan($terakhir, $berhasilTerakhir, $tugas['toleransi_jam']),
            ];
        }

        return $laporan;
    }

    /**
     * Apakah ada satu pun tugas yang perlu diurus.
     */
    public function needsAttention(): bool
    {
        foreach ($this->report() as $baris) {
            if ($baris['keadaan'] !== self::SEHAT) {
                return true;
            }
        }

        return false;
    }

    public function label(string $keadaan): string
    {
        return match ($keadaan) {
            self::SEHAT => 'Berjalan normal',
            self::TERLAMBAT => 'Terlambat',
            self::GAGAL => 'Jalan terakhir gagal',
            default => 'Belum pernah berjalan',
        };
    }

    /**
     * Urutan pemeriksaannya menentukan artinya.
     *
     * Jalan terakhir yang gagal dilaporkan sebagai gagal meskipun baru saja terjadi:
     * tugas yang berjalan tepat waktu lalu meledak bukan tugas yang sehat. Sebaliknya,
     * tugas yang berhasil tetapi sudah lama tidak berjalan dilaporkan terlambat
     * meskipun tidak ada satu pun galat, dan justru itu bentuk kegagalan yang paling
     * sering luput.
     */
    private function keadaan(
        ?ScheduledTaskRun $terakhir,
        ?ScheduledTaskRun $berhasilTerakhir,
        int $toleransiJam,
    ): string {
        if ($terakhir === null) {
            return self::BELUM_PERNAH;
        }

        if (! $terakhir->isSuccess()) {
            return self::GAGAL;
        }

        $batas = Carbon::now()->subHours($toleransiJam);

        return $berhasilTerakhir !== null && $berhasilTerakhir->ran_at->greaterThanOrEqualTo($batas)
            ? self::SEHAT
            : self::TERLAMBAT;
    }
}
