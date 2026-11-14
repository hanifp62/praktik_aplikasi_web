<?php

namespace App\Policies;

use App\Enums\DataErrorState;
use App\Models\DataErrorReport;
use App\Models\User;
use App\Services\DataResmi\Yurisdiksi;

/**
 * Laporan kesalahan data (ruling W8-R17). Pendaki yang masuk boleh melapor lewat rute yang
 * dibatasi laju; yang menandai sudah dicek hanya kurator aktif yang berwenang atas gunung
 * laporan itu, sekali saja. Admin dan moderator bukan kurator.
 */
class DataErrorReportPolicy
{
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    public function check(User $user, DataErrorReport $laporan): bool
    {
        return $laporan->state === DataErrorState::OPEN
            && $this->yurisdiksi->gunung($user)->contains($laporan->mountain_id);
    }
}
