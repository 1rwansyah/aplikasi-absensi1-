<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case NoDone = 'no_done';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Done => 'Disetujui',
            self::NoDone => 'Ditolak',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-400 dark:ring-yellow-500/30',
            self::Done => 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30',
            self::NoDone => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-500/30',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isDone(): bool
    {
        return $this === self::Done;
    }

    public function isRejected(): bool
    {
        return $this === self::NoDone;
    }
}
