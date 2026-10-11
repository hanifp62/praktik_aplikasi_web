<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tautan bagikan perbandingan (FR-23). Tidak ada kolom yang fillable: pemilik, hash
 * token, daftar jalur, dan umurnya ditulis TautanBagikan lewat forceFill sesudah divalidasi,
 * tidak pernah dari badan permintaan. Hash token disembunyikan dari serialisasi.
 */
#[Hidden(['token_hash'])]
class ComparisonShare extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'trail_ids' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Belum dicabut dan belum kedaluwarsa.
     *
     * @param  Builder<ComparisonShare>  $query
     * @return Builder<ComparisonShare>
     */
    public function scopeBerlaku(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
