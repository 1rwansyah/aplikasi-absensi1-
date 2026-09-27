<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Rentang jam dalam satu hari. Mendukung:
 * - Normal: mulai < batas (contoh 12:00–23:00 shift malam)
 * - Lewat tengah malam: mulai > batas (contoh 22:00–06:00)
 */
final class TimeWindow
{
    public function __construct(
        private readonly string $start,
        private readonly ?string $limit,
    ) {}

    public static function create(mixed $start, mixed $limit): self
    {
        return new self(
            TimeFormat::normalized($start),
            $limit === null || $limit === '' ? null : TimeFormat::normalized($limit),
        );
    }

    public function isOvernight(): bool
    {
        return $this->limit !== null && $this->start > $this->limit;
    }

    public function isWithin(Carbon $time): bool
    {
        $t = $time->format('H:i:s');

        return $this->isOvernight()
            ? $t >= $this->start || $t <= $this->limit
            : $t >= $this->start && $t <= $this->limit;
    }

    public function isTooEarly(Carbon $time): bool
    {
        $t = $time->format('H:i:s');

        if ($this->isOvernight()) {
            // Untuk shift malam (misal 22:00 - 06:00), terlalu awal jika waktu
            // berada di antara limit (06:00) dan start (22:00).
            return $t > $this->limit && $t < $this->start;
        }

        return $t < $this->start;
    }

    public function isTooLate(Carbon $time): bool
    {
        if ($this->limit === null) {
            return false;
        }

        $t = $time->format('H:i:s');

        if ($this->isOvernight()) {
            return $t > $this->limit && $t < $this->start;
        }

        return $t > $this->limit;
    }
}
