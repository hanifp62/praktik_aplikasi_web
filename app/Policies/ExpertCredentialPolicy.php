<?php

namespace App\Policies;

use App\Models\ExpertCredential;
use App\Models\User;

/**
 * Memverifikasi kredensial berarti memberi seseorang hak menyunting data yang dibaca
 * pendaki. Hanya admin yang boleh melakukannya (PRD §43).
 */
class ExpertCredentialPolicy
{
    public function verifyCredentials(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ExpertCredential $credential): bool
    {
        return $user->isAdmin() || $credential->user_id === $user->id;
    }
}
