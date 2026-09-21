<?php

namespace App\Exceptions;

use App\Models\Trail;
use RuntimeException;

/**
 * R-008/R-022 dan PRD §110: jalur yang kehilangan data kritis tidak boleh berstatus
 * PUBLISHED.
 *
 * Gerbangnya sebelumnya hidup di satu aksi admin saja, sehingga seeder dan pembuatan
 * model langsung dapat menyalakan `is_published` tanpa melewatinya. Tujuh jalur di
 * basis data lahir lewat celah itu. Pengecualian ini dilemparkan oleh penjaga di model,
 * sehingga pelanggaran berhenti sebagai kesalahan yang terlihat, bukan sebagai baris
 * yang diam-diam tersimpan.
 */
class PublicationGateViolation extends RuntimeException
{
    /**
     * @param  array<int, string>  $missing
     */
    public function __construct(
        public readonly Trail $trail,
        public readonly array $missing,
    ) {
        parent::__construct(sprintf(
            'Jalur "%s" tidak dapat dipublikasikan. %s',
            $trail->name ?? '(tanpa nama)',
            implode(' ', $missing),
        ));
    }
}
