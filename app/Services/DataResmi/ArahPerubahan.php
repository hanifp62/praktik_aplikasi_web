<?php

namespace App\Services\DataResmi;

use App\Enums\OfficialStatusValue;
use App\Models\OfficialStatus;
use App\Services\OfficialStatusService;

/**
 * Arah perubahan sebuah catatan status resmi terhadap status yang terlihat pendaki untuk sasaran
 * yang sama pada tanggal mulainya (aturan terbit asimetris, spec v3 bagian 10.7; ruling W8-R10).
 * Dipakai bersama TerbitStatus (siapa yang boleh menerbitkan) dan PemeriksaanKonsistensi (apa
 * yang diperingatkan), jadi keduanya selalu sepakat.
 */
final class ArahPerubahan
{
    public function __construct(private readonly OfficialStatusService $status) {}

    /**
     * Catatan ini membuka: Buka, atau lebih longgar dari status yang terlihat untuk sasaran yang
     * sama pada tanggal mulainya. Urutan pembatasan: Buka, Belum diketahui, Dibatasi, Tutup.
     */
    public function membuka(OfficialStatus $catatan): bool
    {
        if ($catatan->status === OfficialStatusValue::OPEN) {
            return true;
        }

        $sasaran = $catatan->statusable;
        $sebelumnya = $sasaran === null
            ? OfficialStatusValue::UNKNOWN
            : $this->status->statusOnDate($sasaran, $catatan->effective_at ?? now());

        return self::tingkat($catatan->status) < self::tingkat($sebelumnya);
    }

    public static function tingkat(OfficialStatusValue $nilai): int
    {
        return match ($nilai) {
            OfficialStatusValue::OPEN => 0,
            OfficialStatusValue::UNKNOWN => 1,
            OfficialStatusValue::RESTRICTED => 2,
            OfficialStatusValue::CLOSED => 3,
        };
    }
}
