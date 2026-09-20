<?php

namespace App\Enums;

/**
 * Jenis badan resmi yang diakui sistem ini (PRD §43, §60).
 *
 * Dipisahkan karena otoritasnya berbeda jenis, bukan berbeda tingkat. Balai taman
 * nasional berwenang atas status jalurnya; BNSP berwenang atas kompetensi orangnya.
 * Keduanya resmi, tetapi untuk hal yang berlainan.
 */
enum AuthorityType: string
{
    case NATIONAL_PARK = 'NATIONAL_PARK';
    case GOVERNMENT = 'GOVERNMENT';
    case CERTIFICATION_BODY = 'CERTIFICATION_BODY';
    case PROFESSIONAL_ASSOCIATION = 'PROFESSIONAL_ASSOCIATION';
    case BASECAMP = 'BASECAMP';

    public function label(): string
    {
        return match ($this) {
            self::NATIONAL_PARK => 'Balai taman nasional',
            self::GOVERNMENT => 'Instansi pemerintah',
            self::CERTIFICATION_BODY => 'Lembaga sertifikasi',
            self::PROFESSIONAL_ASSOCIATION => 'Asosiasi profesi',
            self::BASECAMP => 'Pengelola basecamp',
        };
    }

    /**
     * Badan yang berwenang menyatakan status jalur, bukan sekadar menerbitkan sertifikat.
     */
    public function mayDeclareTrailStatus(): bool
    {
        return in_array($this, [self::NATIONAL_PARK, self::GOVERNMENT, self::BASECAMP], true);
    }

    /**
     * Badan yang berwenang menerbitkan atau mengesahkan sertifikat kompetensi.
     */
    public function mayCertify(): bool
    {
        return in_array($this, [self::CERTIFICATION_BODY, self::PROFESSIONAL_ASSOCIATION, self::NATIONAL_PARK], true);
    }
}
