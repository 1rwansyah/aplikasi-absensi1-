<?php

namespace App\Services;

final class ActionResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {}

    public static function ok(string $message): self
    {
        return new self(true, $message);
    }

    public static function fail(string $message): self
    {
        return new self(false, $message);
    }
}
