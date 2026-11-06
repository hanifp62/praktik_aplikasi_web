<?php

namespace App\Models;

use App\Enums\PreparationCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'preparation_template_id', 'category', 'label', 'description',
    'is_critical', 'sort_order', 'applies_when',
])]
class PreparationItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => PreparationCategory::class,
            'is_critical' => 'boolean',
            'applies_when' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PreparationTemplate::class, 'preparation_template_id');
    }

    /**
     * Conditional items only apply to trails matching every key in applies_when.
     * Supported keys: camping_available, min_elevation_gain_m, technical_demand, navigation_complexity.
     */
    public function appliesToTrail(Trail $trail): bool
    {
        foreach ($this->applies_when ?? [] as $key => $expected) {
            $matches = match ($key) {
                'camping_available' => $trail->camping_available === (bool) $expected,
                'min_elevation_gain_m' => ($trail->elevation_gain_m ?? 0) >= (int) $expected,
                'technical_demand' => $trail->technical_demand->value === $expected,
                'navigation_complexity' => $trail->navigation_complexity->value === $expected,
                default => true,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }
}
