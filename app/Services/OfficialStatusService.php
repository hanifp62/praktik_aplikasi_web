<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use Illuminate\Database\Eloquent\Model;

class OfficialStatusService
{
    /**
     * Latest currently-effective official status record for any statusable entity.
     */
    public function currentFor(Model $statusable): ?OfficialStatus
    {
        return OfficialStatus::query()
            ->where('statusable_type', $statusable->getMorphClass())
            ->where('statusable_id', $statusable->getKey())
            ->currentlyEffective()
            ->orderByDesc('effective_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * BR-07: absence of information is reported as UNKNOWN, never assumed to be OPEN.
     */
    public function currentValueFor(Model $statusable): OfficialStatusValue
    {
        return $this->currentFor($statusable)?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * PRD §42: a mountain being OPEN says nothing about its individual trails, so an OPEN
     * mountain never upgrades a trail. Explicit mountain-level restrictions do cascade down.
     */
    public function effectiveStatusForTrail(Trail $trail): OfficialStatusValue
    {
        $trailStatus = $this->currentValueFor($trail);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();

        if (! $mountain instanceof Mountain) {
            return $trailStatus;
        }

        $mountainStatus = $this->currentValueFor($mountain);

        if ($mountainStatus === OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::CLOSED;
        }

        if ($mountainStatus === OfficialStatusValue::RESTRICTED && $trailStatus !== OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::RESTRICTED;
        }

        return $trailStatus;
    }

    /**
     * PRD §42: status dapat melekat pada SEGMENT, bukan hanya gunung dan jalur.
     *
     * Sebuah jalur boleh saja berstatus OPEN sementara salah satu segmennya ditutup —
     * kasus nyata Semeru 2026, ketika pendakian dibuka tetapi hanya sampai Ranu Kumbolo.
     * Pembatasan seperti ini tidak mengeksklusi jalur (PRD §26 memisahkan pembatasan dari
     * pengecualian), tetapi wajib terlihat oleh pendaki.
     *
     * Satu query untuk seluruh segmen, bukan per segmen.
     *
     * @return array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>
     */
    public function segmentRestrictionsForTrail(Trail $trail): array
    {
        $segments = $trail->segments()->get(['id', 'name']);

        if ($segments->isEmpty()) {
            return [];
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->currentlyEffective()
            ->orderByDesc('effective_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $restrictions = [];

        foreach ($segments as $segment) {
            // Status paling akhir berlaku untuk tiap segmen; urutan sudah menurun.
            $latest = $statuses->firstWhere('statusable_id', $segment->id);

            if ($latest === null || ! in_array($latest->status, [
                OfficialStatusValue::CLOSED,
                OfficialStatusValue::RESTRICTED,
            ], true)) {
                continue;
            }

            $restrictions[] = [
                'segment' => $segment->name,
                'status' => $latest->status,
                'reason' => $latest->reason,
            ];
        }

        return $restrictions;
    }

    /**
     * @return array<string, mixed> snapshot used by readiness checks and condition aggregation
     */
    public function snapshotForTrail(Trail $trail): array
    {
        $record = $this->currentFor($trail);
        $status = $this->effectiveStatusForTrail($trail);

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'scope' => $record?->scope?->value,
            'source' => $record?->source,
            'source_url' => $record?->source_url,
            'published_at' => $record?->published_at?->toIso8601String(),
            'verified_at' => $record?->verified_at?->toIso8601String(),
            'fetched_at' => $record?->fetched_at?->toIso8601String(),
            'reason' => $record?->reason,
            'notes' => $record?->notes,
        ];
    }
}
