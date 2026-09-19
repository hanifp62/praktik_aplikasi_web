<?php

namespace App\Enums;

enum ModerationAction: string
{
    case APPROVE = 'APPROVE';
    case REJECT = 'REJECT';
    case FLAG = 'FLAG';
    case REMOVE = 'REMOVE';

    public function label(): string
    {
        return match ($this) {
            self::APPROVE => 'Setujui',
            self::REJECT => 'Tolak',
            self::FLAG => 'Tandai',
            self::REMOVE => 'Hapus',
        };
    }

    public function resultingStatus(): ModerationStatus
    {
        return match ($this) {
            self::APPROVE => ModerationStatus::APPROVED,
            self::REJECT => ModerationStatus::REJECTED,
            self::FLAG => ModerationStatus::FLAGGED,
            self::REMOVE => ModerationStatus::REMOVED,
        };
    }
}
