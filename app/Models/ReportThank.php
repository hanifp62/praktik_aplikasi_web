<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu ucapan terima kasih dari seorang pendaki kepada satu laporan kondisi.
 */
#[Fillable(['trail_condition_report_id', 'user_id'])]
class ReportThank extends Model
{
    public function report(): BelongsTo
    {
        return $this->belongsTo(TrailConditionReport::class, 'trail_condition_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
