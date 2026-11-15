<?php

namespace App\Policies;

use App\Models\HikingHistory;
use App\Models\User;

/**
 * Riwayat pendakian milik satu pendaki (PRD §119, FR-25). Catatan manual dapat diperbaiki dan
 * dihapus pemiliknya; riwayat dari trip tidak lewat sini, karena angkanya diubah di ringkasan
 * pendakian ("Ubah angka") dan barisnya bergantung pada trip. Mengetahui id baris tidak sama
 * dengan memilikinya.
 */
class HikingHistoryPolicy
{
    public function update(User $user, HikingHistory $riwayat): bool
    {
        return $riwayat->user_id === $user->id && $riwayat->manual();
    }

    public function delete(User $user, HikingHistory $riwayat): bool
    {
        return $this->update($user, $riwayat);
    }
}
