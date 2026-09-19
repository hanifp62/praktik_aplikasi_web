<?php

namespace App\Models;

use App\Enums\AnalyticsEvent as AnalyticsEventName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'event_name', 'payload'])]
class AnalyticsEvent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_name' => AnalyticsEventName::class,
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
