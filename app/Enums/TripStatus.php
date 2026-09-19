<?php

namespace App\Enums;

enum TripStatus: string
{
    case DRAFT = 'DRAFT';
    case PLANNED = 'PLANNED';
    case READY_FOR_DEPARTURE = 'READY_FOR_DEPARTURE';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PLANNED => 'Direncanakan',
            self::READY_FOR_DEPARTURE => 'Siap berangkat',
            self::IN_PROGRESS => 'Sedang berlangsung',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    /**
     * Transisi yang sah dari status ini (PRD §35).
     *
     * COMPLETED dan CANCELLED bersifat final: trip yang sudah dibatalkan tidak boleh
     * dimulai, dan trip yang sudah selesai tidak boleh dibatalkan surut.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PLANNED, self::CANCELLED],
            self::PLANNED => [self::READY_FOR_DEPARTURE, self::IN_PROGRESS, self::CANCELLED],
            self::READY_FOR_DEPARTURE => [self::IN_PROGRESS, self::PLANNED, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED, self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
