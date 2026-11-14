<?php

namespace App\Models;

use App\Enums\PersonalTargetMetric;
use Database\Factories\PersonalTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Target pribadi bulanan seorang pendaki (FR-25). Satu baris per ukuran; pemiliknya selalu dari
 * sesi, tidak pernah dari isian (PersonalTargetController).
 */
#[Fillable(['metric', 'monthly_value'])]
class PersonalTarget extends Model
{
    /** @use HasFactory<PersonalTargetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric' => PersonalTargetMetric::class,
            'monthly_value' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
