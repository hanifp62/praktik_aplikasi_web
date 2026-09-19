<?php

namespace App\Services;

use App\Enums\PreparationStatus;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Preparation is derived from the selected route, not a generic checklist (PRD §36).
 */
class PreparationService
{
    /**
     * Builds the trip checklist from the trail-specific template when one exists, otherwise
     * from the default template. Existing item statuses are preserved on regeneration.
     */
    public function generateFor(TripPlan $trip): Collection
    {
        $trip->loadMissing('trail');
        $items = $this->applicableItems($trip);

        return DB::transaction(function () use ($trip, $items) {
            $existing = $trip->preparationItems()->get()->keyBy('preparation_item_id');

            foreach ($items as $item) {
                $current = $existing->get($item->id);

                TripPreparationItem::updateOrCreate(
                    ['trip_plan_id' => $trip->id, 'preparation_item_id' => $item->id],
                    [
                        'category' => $item->category->value,
                        'label' => $item->label,
                        'description' => $item->description,
                        'is_critical' => $item->is_critical,
                        'status' => $current?->status?->value ?? PreparationStatus::NOT_CONFIRMED->value,
                    ]
                );
            }

            return $trip->preparationItems()->get();
        });
    }

    /**
     * @return Collection<int, PreparationItem>
     */
    private function applicableItems(TripPlan $trip): Collection
    {
        $template = PreparationTemplate::query()
            ->where('trail_id', $trip->trail_id)
            ->with('items')
            ->first();

        $defaults = PreparationTemplate::query()
            ->where('is_default', true)
            ->with('items')
            ->get()
            ->flatMap->items;

        $items = $defaults->concat($template?->items ?? collect());

        return $items
            ->filter(fn (PreparationItem $item) => $item->appliesToTrail($trip->trail))
            ->unique('id')
            ->values();
    }

    public function updateStatus(TripPreparationItem $item, PreparationStatus $status): TripPreparationItem
    {
        $item->update([
            'status' => $status,
            'status_updated_at' => now(),
        ]);

        return $item;
    }

    /**
     * @return array<string, mixed> snapshot consumed by ReadinessService
     */
    public function state(TripPlan $trip): array
    {
        $trip->loadMissing('preparationItems');
        $items = $trip->preparationItems;

        return [
            'total_items' => $items->count(),
            'confirmed' => $items->where('status', PreparationStatus::CONFIRMED)->count(),
            'not_confirmed' => $items->where('status', PreparationStatus::NOT_CONFIRMED)->count(),
            'not_applicable' => $items->where('status', PreparationStatus::NOT_APPLICABLE)->count(),
            'completion_percent' => $trip->preparationCompletionPercent(),
            'critical_outstanding' => $items
                ->where('is_critical', true)
                ->where('status', PreparationStatus::NOT_CONFIRMED)
                ->pluck('label')
                ->values()
                ->all(),
        ];
    }
}
