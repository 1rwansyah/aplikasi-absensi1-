<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeFaceDescriptorService
{
    public function __construct(
        private readonly FaceVerificationService $faceVerification,
    ) {}

    public function save(Employee $employee, array $descriptor): void
    {
        if ($error = $this->faceVerification->validateDescriptorPayload($descriptor)) {
            throw new \InvalidArgumentException($error);
        }

        $employee->update([
            'face_descriptor' => json_encode(array_map('floatval', $descriptor)),
        ]);
    }

    public function clear(Employee $employee): void
    {
        $employee->update(['face_descriptor' => null]);
    }

    public function decodeFromRequest(?string $json): ?array
    {
        if (! filled($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_map('floatval', $decoded);
    }
}
