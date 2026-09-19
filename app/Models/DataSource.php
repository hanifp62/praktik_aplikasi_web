<?php

namespace App\Models;

use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'source_name', 'source_type', 'source_url', 'source_owner', 'retrieved_at',
    'verified_at', 'freshness_policy', 'verification_status', 'notes',
])]
class DataSource extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source_type' => SourceType::class,
            'verification_status' => VerificationStatus::class,
            'retrieved_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function officialStatuses(): HasMany
    {
        return $this->hasMany(OfficialStatus::class);
    }
}
