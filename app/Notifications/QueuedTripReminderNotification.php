<?php

namespace App\Notifications;

use App\Models\TripPlan;
use App\Models\TripReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

/**
 * Varian antrean latar untuk surel pengingat perjalanan (GAP-14, FR-24).
 *
 * Dipakai ketika `hiking.reminders.queued` bernilai true pada deployment yang menjalankan
 * pekerja antrean. Bila seluruh percobaan gagal, klaim `sent_at` dilepas supaya jalan
 * penjadwal berikutnya dapat mencoba ulang selama waktunya masih relevan.
 */
class QueuedTripReminderNotification extends TripReminderNotification implements ShouldQueue
{
    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public function __construct(
        TripPlan $trip,
        int $sisaHari,
        public readonly ?int $reminderId = null,
    ) {
        parent::__construct($trip, $sisaHari);
    }

    public function failed(Throwable $e): void
    {
        if ($this->reminderId !== null) {
            TripReminder::query()->whereKey($this->reminderId)->update(['sent_at' => null]);
        }

        report($e);
    }
}
