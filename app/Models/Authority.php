<?php

namespace App\Models;

use App\Enums\AuthorityType;
use Database\Factories\AuthorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Badan resmi: balai taman nasional, lembaga sertifikasi, asosiasi profesi, pengelola
 * basecamp, instansi pemerintah (PRD §43).
 *
 * Sebelumnya pihak resmi hanya berupa teks bebas, sehingga sistem tidak dapat menyebut
 * siapa yang ditunggu ketika sebuah data belum ada.
 */
#[Fillable([
    'name', 'slug', 'type', 'abbreviation', 'website', 'contact', 'jurisdiction',
    'notes', 'verified_at',
])]
class Authority extends Model
{
    /** @use HasFactory<AuthorityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AuthorityType::class,
            'verified_at' => 'datetime',
        ];
    }

    public function mountains(): BelongsToMany
    {
        return $this->belongsToMany(Mountain::class)->withTimestamps();
    }

    public function issuedCredentials(): HasMany
    {
        return $this->hasMany(ExpertCredential::class, 'issuing_authority_id');
    }

    public function endorsedCredentials(): HasMany
    {
        return $this->hasMany(ExpertCredential::class, 'endorsing_authority_id');
    }

    /**
     * Anggota meja kurator badan ini (ruling W8-R7), termasuk yang sudah dicabut.
     */
    public function members(): HasMany
    {
        return $this->hasMany(AuthorityMember::class);
    }

    public function displayName(): string
    {
        return $this->abbreviation ? $this->name.' ('.$this->abbreviation.')' : $this->name;
    }
}
