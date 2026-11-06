<?php

namespace App\Models;

use App\Enums\ModerationAction as ModerationActionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['moderator_id', 'moderatable_type', 'moderatable_id', 'action', 'reason'])]
class ModerationAction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'action' => ModerationActionType::class,
        ];
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }
}
