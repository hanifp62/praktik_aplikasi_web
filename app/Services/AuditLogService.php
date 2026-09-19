<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Audit trail for the critical actions listed in PRD §61.
 */
class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(?User $actor, string $action, ?Model $entity = null, ?array $before = null, ?array $after = null): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entity?->getMorphClass(),
            'entity_id' => $entity?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Records a model mutation, capturing only the attributes that actually changed.
     */
    public function recordChange(?User $actor, string $action, Model $entity): AuditLog
    {
        $changes = $entity->getChanges();
        $before = array_intersect_key($entity->getOriginal(), $changes);

        return $this->record($actor, $action, $entity, $before ?: null, $changes ?: null);
    }
}
