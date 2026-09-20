<?php

namespace App\Policies;

use App\Models\User;

/**
 * Badan resmi menentukan siapa yang berwenang atas sebuah kawasan, dan dari situ
 * mengalir hak menyunting data. Hanya admin yang boleh mengubahnya (PRD §43).
 */
class AuthorityPolicy
{
    public function manageAuthorities(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
