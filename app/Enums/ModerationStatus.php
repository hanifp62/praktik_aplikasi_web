<?php

namespace App\Enums;

enum ModerationStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case FLAGGED = 'FLAGGED';
    case REMOVED = 'REMOVED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu moderasi',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::FLAGGED => 'Ditandai',
            self::REMOVED => 'Dihapus',
        };
    }

    /**
     * Status yang dibaca pelapor laporannya sendiri. FLAGGED adalah tanda kerja moderator
     * (laporan ditandai untuk ditinjau lebih teliti), jadi bagi pelapor laporan itu masih
     * menunggu moderasi, sama dengan Ringkasan (HasilPendakian::laporan) dan Progres
     * (HikerProgressController, ruling AF-3). Antrean moderasi tetap memakai label().
     */
    public function labelUntukPelapor(): string
    {
        return match ($this) {
            self::PENDING, self::FLAGGED => self::PENDING->label(),
            default => $this->label(),
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::APPROVED;
    }
}
