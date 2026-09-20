<?php

namespace App\Models;

use App\Enums\CredentialLevel;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Sertifikat kompetensi seseorang, beserta badan yang menerbitkan dan yang mengesahkannya
 * untuk kawasan tertentu.
 *
 * Skemanya mengikuti kenyataan: BNSP menetapkan standar, LSP menguji, APGI menaungi,
 * jenjangnya Muda, Madya, Ahli menurut SKKNI, dan sertifikatnya berlaku tiga tahun.
 *
 * Masa berlaku itu bukan hiasan. Hak menyumbang data gugur sendiri ketika sertifikatnya
 * kedaluwarsa, tanpa perlu ada yang ingat mencabutnya.
 */
#[Fillable([
    'user_id', 'issuing_authority_id', 'endorsing_authority_id', 'scheme', 'level',
    'certificate_number', 'issued_at', 'expires_at', 'verification_status',
    'verified_by', 'verified_at', 'notes',
])]
class ExpertCredential extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => CredentialLevel::class,
            'verification_status' => VerificationStatus::class,
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuingAuthority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'issuing_authority_id');
    }

    public function endorsingAuthority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'endorsing_authority_id');
    }

    public function mountains(): BelongsToMany
    {
        return $this->belongsToMany(Mountain::class, 'credential_mountain')->withTimestamps();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Sertifikat yang benar-benar berlaku: sudah kami verifikasi, dan belum lewat masa
     * berlakunya. Yang belum diverifikasi diperlakukan seperti tidak ada, bukan seperti
     * ada tetapi meragukan.
     */
    public function isCurrentlyValid(): bool
    {
        return $this->verification_status === VerificationStatus::VERIFIED && ! $this->isExpired();
    }

    /**
     * Hak menyumbang data jalur: harus berlaku, dan harus jenjang tertinggi.
     */
    public function mayContributeTrailData(): bool
    {
        return $this->isCurrentlyValid() && $this->level->mayContributeTrailData();
    }

    public function coversMountain(int $mountainId): bool
    {
        return $this->mountains->contains('id', $mountainId);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('verification_status', VerificationStatus::VERIFIED->value)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()));
    }
}
