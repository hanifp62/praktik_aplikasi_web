<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu jalur yang disimpan seorang pendaki, beserta catatan pribadinya (FR-22).
 *
 * `note` sengaja tidak fillable: ia ditulis lewat ConsiderationService::simpanCatatan()
 * setelah barisnya dipastikan milik penulisnya, tidak pernah lewat pengisian massal.
 */
#[Fillable(['user_id', 'trail_id'])]
class TrailConsideration extends Model
{
    use HasFactory;

    /** Batas panjang catatan, sama dengan lebar kolom `note`. */
    public const CATATAN_MAKS = 500;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }
}
