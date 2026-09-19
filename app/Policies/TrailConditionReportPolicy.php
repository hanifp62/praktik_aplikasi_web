<?php

namespace App\Policies;

use App\Enums\ModerationStatus;
use App\Models\TrailConditionReport;
use App\Models\User;

class TrailConditionReportPolicy
{
    public function view(User $user, TrailConditionReport $report): bool
    {
        return $report->moderation_status === ModerationStatus::APPROVED
            || $report->user_id === $user->id
            || $user->isModerator();
    }

    public function update(User $user, TrailConditionReport $report): bool
    {
        // Once moderated, only moderators may change a report.
        return $report->user_id === $user->id && $report->moderation_status === ModerationStatus::PENDING;
    }

    public function delete(User $user, TrailConditionReport $report): bool
    {
        return $report->user_id === $user->id || $user->isModerator();
    }

    public function moderate(User $user): bool
    {
        return $user->isModerator();
    }
}
