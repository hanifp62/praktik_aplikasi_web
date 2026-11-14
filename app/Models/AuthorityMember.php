<?php

namespace App\Models;

use App\Enums\AuthorityMemberRole;
use App\Support\Timezone;
use Database\Factories\AuthorityMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keanggotaan seorang pengguna di sebuah badan resmi untuk meja kurator (spec v3 bagian 10.7;
 * ruling W8-R7). Pengguna, badan, pemberi, dan pencabut ditulis controller admin lewat forceFill,
 * tidak pernah dari isian.
 */
#[Fillable(['role', 'appointment_reference', 'valid_from', 'valid_until'])]
class AuthorityMember extends Model
{
    /** @use HasFactory<AuthorityMemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => AuthorityMemberRole::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    /**
     * Keanggotaan yang sedang berlaku: belum dicabut dan tanggal hari ini di dalam masa
     * berlakunya. "Hari ini" adalah tanggal WIT, zona Indonesia yang paling dulu berganti hari,
     * jadi masa berlaku tidak pernah terasa lebih panjang di zona mana pun.
     */
    public function scopeAktif(Builder $query): Builder
    {
        $hariIni = Timezone::latestDateInIndonesia();

        return $query
            ->whereNull('revoked_at')
            ->whereDate('valid_from', '<=', $hariIni)
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $hariIni));
    }
}
