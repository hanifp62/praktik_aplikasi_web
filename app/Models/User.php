<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function expertCredentials(): HasMany
    {
        return $this->hasMany(ExpertCredential::class);
    }

    /**
     * Kredensial yang benar-benar memberi hak menyumbang data jalur untuk satu gunung.
     *
     * Mengembalikan kredensialnya, bukan sekadar benar atau salah, supaya pemanggil dapat
     * mencatat sertifikat mana yang dipakai. Siapa menyumbang atas dasar apa adalah fakta
     * yang harus tersimpan, bukan disimpulkan belakangan.
     */
    public function usableTrailCredentialFor(int $mountainId): ?ExpertCredential
    {
        return $this->expertCredentials()
            ->usable()
            ->with('mountains')
            ->get()
            ->first(fn (ExpertCredential $k) => $k->mayContributeTrailData() && $k->coversMountain($mountainId));
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function experience(): HasOne
    {
        return $this->hasOne(UserExperience::class);
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function hikingGoals(): HasMany
    {
        return $this->hasMany(HikingGoal::class);
    }

    public function tripPlans(): HasMany
    {
        return $this->hasMany(TripPlan::class);
    }

    public function conditionReports(): HasMany
    {
        return $this->hasMany(TrailConditionReport::class);
    }

    public function hikingHistory(): HasMany
    {
        return $this->hasMany(HikingHistory::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [UserRole::MODERATOR, UserRole::ADMIN], true);
    }

    /**
     * PRD FR-02: recommendations require a minimum profile before they mean anything.
     */
    public function hasCompletedProfile(): bool
    {
        return $this->profile?->completed_at !== null;
    }
}
