<?php

namespace App\Policies;

use App\Models\Mountain;
use App\Models\User;
use App\Services\DataResmi\Yurisdiksi;

/**
 * Status gunung api PVMBG (DK-43; ruling W8-R18): dicatat kurator aktif, hanya untuk gunung di
 * kawasan badannya. Admin dan moderator bukan kurator.
 */
class VolcanoStatusPolicy
{
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    public function viewAny(User $user): bool
    {
        return $this->yurisdiksi->kuratorAktif($user);
    }

    public function create(User $user, Mountain $gunung): bool
    {
        return $this->yurisdiksi->berwenang($user, $gunung);
    }
}
