<?php

namespace App\Models;

use App\Enums\VolcanoLevel;
use Database\Factories\VolcanoStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pencatatan level gunung api PVMBG (DK-43; ruling W8-R18). Gunung, pencatat, dan waktu
 * verifikasi ditulis server lewat forceFill; isian hanya level, rekomendasi, tautan laporan, dan
 * waktu terbitnya.
 */
#[Fillable(['level', 'recommendation', 'report_url', 'issued_at'])]
class VolcanoStatus extends Model
{
    /** @use HasFactory<VolcanoStatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => VolcanoLevel::class,
            'issued_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
