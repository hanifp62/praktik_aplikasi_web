<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\FollowMountainRequest;
use App\Models\Mountain;
use App\Models\MountainFollow;
use Illuminate\Http\RedirectResponse;

/**
 * Mengikuti kabar sebuah gunung, atau berhenti mengikutinya.
 *
 * Gunungnya yang diikuti, bukan jalurnya: penutupan hampir selalu diumumkan untuk
 * kawasan, dan pendaki yang mengikuti satu jalur Merbabu tetap perlu tahu ketika seluruh
 * Merbabu ditutup.
 */
class MountainFollowController extends Controller
{
    public function store(FollowMountainRequest $request, Mountain $mountain): RedirectResponse
    {
        // Batasnya sama dengan sebelum migrasi, ketika aksi ini hidup di halaman jalur:
        // hanya gunung yang masih punya jalur beredar. Gunung yang seluruh jalurnya
        // terarsip tidak dapat diikuti lewat id yang ditebak.
        abort_unless($mountain->trails()->whereNull('archived_at')->exists(), 404);

        $kunci = ['user_id' => $request->user()->id, 'mountain_id' => $mountain->id];

        $request->boolean('ya')
            ? MountainFollow::createOrFirst($kunci)
            : MountainFollow::where($kunci)->delete();

        return back();
    }
}
