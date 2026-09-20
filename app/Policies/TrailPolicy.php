<?php

namespace App\Policies;

use App\Models\Trail;
use App\Models\User;

/**
 * Siapa boleh menyentuh data jalur (PRD §43, §110).
 *
 * Admin boleh semuanya. Selain itu, seorang pemandu bersertifikat jenjang Ahli yang
 * sertifikatnya masih berlaku dan kawasannya mencakup gunung ini boleh mengisi datanya.
 *
 * Yang TIDAK ikut dilonggarkan adalah penerbitan dan pengarsipan. Ahli menyiapkan
 * datanya, admin yang memutuskan data itu layak dilihat pendaki. Pemisahan ini yang
 * membuat pelonggaran akses tetap aman: gerbang §110 tidak berpindah tangan.
 */
class TrailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->expertCredentials()->usable()->exists();
    }

    /**
     * Membuat jalur baru tetap milik admin. Ahli menyumbang data untuk jalur yang sudah
     * dikenali sistem, bukan menambah jalur yang belum diakui siapa pun.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Trail $trail): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->usableTrailCredentialFor($trail->mountain_id) !== null;
    }

    public function publish(User $user, Trail $trail): bool
    {
        // PRD §110: publishing requires the minimum data quality set.
        return $user->isAdmin() && $trail->meetsPublishingRequirements();
    }

    public function archive(User $user, Trail $trail): bool
    {
        return $user->isAdmin();
    }
}
