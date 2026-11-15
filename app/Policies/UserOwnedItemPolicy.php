<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserOwnedItem;

/**
 * Perlengkapan pribadi milik satu pendaki (rencana induk W7 butir 4). Mengetahui id barang tidak
 * sama dengan memilikinya (PRD §119).
 */
class UserOwnedItemPolicy
{
    public function update(User $user, UserOwnedItem $barang): bool
    {
        return $barang->user_id === $user->id;
    }

    public function delete(User $user, UserOwnedItem $barang): bool
    {
        return $this->update($user, $barang);
    }
}
