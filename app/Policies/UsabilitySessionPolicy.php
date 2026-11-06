<?php

namespace App\Policies;

use App\Models\User;

/**
 * Catatan studi kegunaan berisi kutipan peserta dan pengamatan tentang di mana mereka
 * tersesat. Itu bahan penelitian tentang orang, bukan isi aplikasi, dan tidak ada
 * alasan pendaki lain dapat membacanya.
 */
class UsabilitySessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
