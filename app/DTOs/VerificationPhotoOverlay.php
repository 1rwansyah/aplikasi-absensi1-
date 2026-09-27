<?php

namespace App\DTOs;

final class VerificationPhotoOverlay
{
    public function __construct(
        public readonly string $time,
        public readonly string $dateLabel,
        public readonly string $dayLabel,
        public readonly string $address,
    ) {}
}
