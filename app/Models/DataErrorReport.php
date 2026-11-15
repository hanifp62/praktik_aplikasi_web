<?php

namespace App\Models;

use App\Enums\DataErrorCategory;
use App\Enums\DataErrorState;
use Database\Factories\DataErrorReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan kesalahan data dari layar pendaki (spec v3 bagian 10.7 langkah 7; ruling W8-R17).
 * Hanya bagian dan isi yang diisi pendaki; pelapor, gunung, jalur, catatan status yang terbaca,
 * keadaan, dan pemeriksa ditulis server lewat forceFill, tidak pernah dari isian.
 */
#[Fillable(['category', 'note'])]
class DataErrorReport extends Model
{
    /** @use HasFactory<DataErrorReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => DataErrorCategory::class,
            'state' => DataErrorState::class,
            'checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function officialStatus(): BelongsTo
    {
        return $this->belongsTo(OfficialStatus::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->where('state', DataErrorState::OPEN->value);
    }
}
