<?php

namespace App\Models;

use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Services\OfficialStatusService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'trail_id', 'hiking_goal_id', 'name', 'planned_date',
    'start_time', 'trip_type', 'notes', 'status', 'completed_at',
])]
class TripPlan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'trip_type' => TripType::class,
            'status' => TripStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function hikingGoal(): BelongsTo
    {
        return $this->belongsTo(HikingGoal::class);
    }

    public function preparationItems(): HasMany
    {
        return $this->hasMany(TripPreparationItem::class);
    }

    public function readinessChecks(): HasMany
    {
        return $this->hasMany(ReadinessCheck::class);
    }

    public function latestReadinessCheck(): HasOne
    {
        return $this->hasOne(ReadinessCheck::class)->latestOfMany();
    }

    /**
     * Apakah vonis kesiapan tersimpan sudah didahului keadaan.
     *
     * Halaman kesiapan menghitung ulang setiap kali dibuka, jadi ia selalu benar.
     * Ringkasan di dasbor, daftar trip, dan halaman trip membaca baris tersimpan, dan
     * baris itu mencatat status resmi yang dipakainya. Ketika status jalur sekarang
     * berbeda dari yang tercatat, vonisnya dinilai atas keadaan yang sudah berlalu.
     *
     * Aplikasi ini sudah menolak pola yang sama di pintu lain: service worker tidak
     * pernah menyajikan halaman dari cache karena status "BUKA" yang basi lebih
     * berbahaya daripada halaman yang gagal terbuka (§94, §95).
     *
     * Status sekarang boleh dioper dari luar supaya daftar dapat memuatnya sekali untuk
     * seluruh baris, alih-alih satu query per trip.
     */
    public function readinessIsStale(?OfficialStatusValue $statusSekarang = null): bool
    {
        $dinilaiDengan = $this->latestReadinessCheck?->official_status_snapshot['status'] ?? null;

        if ($dinilaiDengan === null) {
            return false;
        }

        $statusSekarang ??= app(OfficialStatusService::class)->effectiveStatusForTrail($this->trail);

        return $dinilaiDengan !== $statusSekarang->value;
    }

    public function hikingSession(): HasOne
    {
        return $this->hasOne(HikingSession::class);
    }

    public function history(): HasOne
    {
        return $this->hasOne(HikingHistory::class);
    }

    public function preparationCompletionPercent(): int
    {
        $items = $this->preparationItems;
        $applicable = $items->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        if ($applicable->isEmpty()) {
            return 0;
        }

        $confirmed = $applicable->where('status', PreparationStatus::CONFIRMED)->count();

        return (int) round($confirmed / $applicable->count() * 100);
    }

    public function hasUnconfirmedCriticalItems(): bool
    {
        return $this->preparationItems
            ->where('is_critical', true)
            ->contains(fn (TripPreparationItem $item) => $item->status === PreparationStatus::NOT_CONFIRMED);
    }
}
