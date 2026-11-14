<?php

namespace App\Models;

use Database\Factories\UserOwnedItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu barang perlengkapan pribadi (spec v3 bagian 6.15, ruling W7-R8). Pemiliknya selalu dari
 * sesi; hanya nama yang dapat diisi.
 */
#[Fillable(['name'])]
class UserOwnedItem extends Model
{
    /** @use HasFactory<UserOwnedItemFactory> */
    use HasFactory;

    /** Batas daftar per pendaki: cukup untuk satu ransel lengkap, dan menahan baris tanpa ujung. */
    public const BATAS = 40;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
