<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu kali jalannya sebuah tugas terjadwal.
 */
#[Fillable(['task', 'status', 'runtime_ms', 'summary', 'ran_at'])]
class ScheduledTaskRun extends Model
{
    public const SUCCESS = 'SUCCESS';

    public const FAILED = 'FAILED';

    protected function casts(): array
    {
        return [
            'ran_at' => 'datetime',
        ];
    }

    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->where('status', self::SUCCESS);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS;
    }
}
