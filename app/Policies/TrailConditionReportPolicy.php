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

    /**
     * "Ubah sebelum dimoderasi" ditawarkan (HasilPendakian::laporan()): pemiliknya, selama
     * laporan masih menunggu moderasi. Sesudah moderator bertindak, laporan dibaca apa adanya.
     */
    public function update(User $user, TrailConditionReport $report): bool
    {
        return $this->submitUpdate($user, $report) && $report->moderation_status === ModerationStatus::PENDING;
    }

    /**
     * Mengirim perubahan laporan (UpdateConditionReportRequest, ruling W6-6r huruf b): hanya
     * pemiliknya. Status TIDAK diperiksa di sini, karena status yang dibaca saat otorisasi dapat
     * berubah sebelum tulisannya terjadi; ConditionReportController::update() menulis lewat
     * UPDATE bersyarat "masih PENDING" dan menolak dengan kalimat bila tidak ada baris yang
     * terubah.
     */
    public function submitUpdate(User $user, TrailConditionReport $report): bool
    {
        return $report->user_id === $user->id;
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
