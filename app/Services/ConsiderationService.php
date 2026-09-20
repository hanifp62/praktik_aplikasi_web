<?php

namespace App\Services;

use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Jalur yang sedang ditimbang.
 *
 * Batasnya lima dan keras. Lebih dari lima kolom tidak terbaca pada lebar 400px (§88),
 * dan riset AllTrails menunjukkan beban keputusan justru naik ketika pilihan menumpuk:
 * daftar panjang menunda keputusan alih-alih memperbaikinya.
 */
class ConsiderationService
{
    public const BATAS = 5;

    /**
     * @return bool true bila jalurnya masuk, false bila keluar atau ditolak
     */
    public function toggle(User $user, Trail $trail): bool
    {
        $ada = TrailConsideration::where('user_id', $user->id)
            ->where('trail_id', $trail->id)
            ->first();

        if ($ada) {
            $ada->delete();

            return false;
        }

        // Yang keenam ditolak, bukan menggeser yang tertua keluar. Menggeser diam-diam
        // menghilangkan jalur yang sedang ditimbang tepat ketika ia sedang ditimbang.
        if ($this->forUser($user)->count() >= self::BATAS) {
            return false;
        }

        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        return true;
    }

    /**
     * @return Collection<int, Trail>
     */
    public function forUser(User $user): Collection
    {
        return Trail::query()
            ->active()
            ->whereIn('id', TrailConsideration::where('user_id', $user->id)->pluck('trail_id'))
            ->with('mountain')
            ->get();
    }
}
