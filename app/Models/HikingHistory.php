<?php

namespace App\Models;

use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Enums\TripType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'source', 'trip_plan_id', 'trail_id', 'trail_name', 'trail_condition_report_id', 'trip_type',
    'completion_state', 'preparation_completion_percent', 'personal_notes', 'completed_at',
    'recorded_distance_m', 'recorded_gain_m', 'recorded_moving_minutes', 'recorded_highest_m', 'numbers_edited_at',
])]
class HikingHistory extends Model
{
    use HasFactory;

    protected $table = 'hiking_history';

    /**
     * Baris yang dibuat siklus trip tidak menyebut asalnya; bawaannya TRIP, sama dengan kolomnya.
     *
     * @var array<string, string>
     */
    protected $attributes = ['source' => 'TRIP'];

    protected function casts(): array
    {
        return [
            'source' => HikeRecordSource::class,
            'trip_type' => TripType::class,
            'completion_state' => CompletionState::class,
            'completed_at' => 'datetime',
            'recorded_distance_m' => 'integer',
            'recorded_gain_m' => 'integer',
            'recorded_moving_minutes' => 'integer',
            'recorded_highest_m' => 'integer',
            'numbers_edited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function conditionReport(): BelongsTo
    {
        return $this->belongsTo(TrailConditionReport::class, 'trail_condition_report_id');
    }

    /**
     * Nama jalur untuk ditampilkan: jalur katalog bila ada, nama yang ditulis pendaki bila
     * jalurnya di luar katalog (catatan manual).
     */
    public function namaJalur(): string
    {
        return $this->trail?->name ?? (string) $this->trail_name;
    }

    public function manual(): bool
    {
        return $this->source === HikeRecordSource::MANUAL;
    }
}
