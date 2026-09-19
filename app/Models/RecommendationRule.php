<?php

namespace App\Models;

use App\Enums\CompatibilityFactor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'category', 'description', 'weight', 'active', 'engine_version'])]
class RecommendationRule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function factor(): ?CompatibilityFactor
    {
        return CompatibilityFactor::tryFrom($this->key);
    }
}
