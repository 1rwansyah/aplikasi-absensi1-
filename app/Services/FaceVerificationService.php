<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class FaceVerificationService
{
    public function matchThreshold(): float
    {
        return (float) config('face.match_threshold', 0.5);
    }

    public function minMatchPercent(): int
    {
        return max(0, min(100, (int) config('face.min_match_percent', 74)));
    }

    public function descriptorLength(): int
    {
        return (int) config('face.descriptor_length', 128);
    }

    public function euclideanDistance(array $descriptorA, array $descriptorB): float
    {
        $length = min(count($descriptorA), count($descriptorB));
        $sum = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $diff = (float) $descriptorA[$i] - (float) $descriptorB[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    public function isMatch(array $stored, array $live): bool
    {
        $percent = $this->matchPercentFromDistance(
            $this->euclideanDistance($stored, $live)
        );

        return $this->isMatchPercentAccepted($percent);
    }

    public function isMatchPercentAccepted(?int $percent): bool
    {
        return $percent !== null && $percent >= $this->minMatchPercent();
    }

    public function validateDescriptorPayload(?array $descriptor): ?string
    {
        if ($descriptor === null || $descriptor === []) {
            return 'Descriptor wajah tidak valid.';
        }

        if (count($descriptor) !== $this->descriptorLength()) {
            return 'Descriptor wajah tidak lengkap.';
        }

        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                return 'Descriptor wajah mengandung nilai tidak valid.';
            }
        }

        return null;
    }

    public function verifyForUser(User $user, array $liveDescriptor, int $facesDetected): array
    {
        $employee = $user->employee;

        if ($employee === null) {
            return [
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan. Hubungi HR/Admin.',
            ];
        }

        return $this->verifyForEmployee($employee, $liveDescriptor, $facesDetected);
    }

    public function verifyForEmployee(Employee $employee, array $liveDescriptor, int $facesDetected): array
    {
        if (! $employee->hasProfilePhoto()) {
            return [
                'success' => false,
                'message' => 'Foto profil belum diunggah. Hubungi HR/Admin untuk melengkapi foto profil.',
            ];
        }

        if (! $employee->hasFaceDescriptor()) {
            return [
                'success' => false,
                'message' => 'Data wajah dari foto profil belum siap. Buka halaman absensi sekali lagi atau hubungi HR/Admin.',
            ];
        }

        if ($facesDetected !== 1) {
            return [
                'success' => false,
                'message' => $facesDetected < 1
                    ? 'Wajah tidak terdeteksi. Pastikan wajah terlihat jelas di kamera.'
                    : 'Terdeteksi lebih dari satu wajah. Hanya satu wajah yang diperbolehkan.',
            ];
        }

        if ($error = $this->validateDescriptorPayload($liveDescriptor)) {
            return ['success' => false, 'message' => $error];
        }

        $stored = $employee->faceDescriptorArray();

        if ($stored === null) {
            return [
                'success' => false,
                'message' => 'Data wajah dari foto profil tidak valid. Hubungi HR/Admin.',
            ];
        }

        $distance = $this->euclideanDistance($stored, $liveDescriptor);
        $matchPercent = $this->matchPercentFromDistance($distance);
        $matched = $this->isMatchPercentAccepted($matchPercent);

        return [
            'success' => $matched,
            'matched' => $matched,
            'distance' => round($distance, 4),
            'match_percent' => $matchPercent,
            'min_match_percent' => $this->minMatchPercent(),
            'threshold' => $this->matchThreshold(),
            'message' => $matched
                ? 'Verifikasi wajah berhasil (sesuai foto profil).'
                : $this->rejectMessageForMatchPercent($matchPercent),
        ];
    }

    private function rejectMessageForMatchPercent(?int $matchPercent): string
    {
        if ($matchPercent !== null && $matchPercent < $this->minMatchPercent()) {
            return "Kecocokan wajah {$matchPercent}% (minimal {$this->minMatchPercent()}%). Silakan absen ulang dengan wajah yang jelas.";
        }

        return 'Wajah tidak cocok dengan foto profil Anda.';
    }

    public function matchPercentFromDistance(?float $distance): ?int
    {
        if ($distance === null) {
            return null;
        }

        $anchors = [
            ['dist' => 0.0, 'pct' => 100],
            ['dist' => 0.2, 'pct' => 98],
            ['dist' => 0.3, 'pct' => 92],
            ['dist' => 0.4, 'pct' => 85],
            ['dist' => 0.5, 'pct' => 75],
            ['dist' => 0.65, 'pct' => 55],
            ['dist' => 0.8, 'pct' => 35],
            ['dist' => 1.0, 'pct' => 10],
        ];

        $value = max(0.0, $distance);
        $last = $anchors[array_key_last($anchors)];

        if ($value >= $last['dist']) {
            return max(0, $last['pct']);
        }

        for ($i = 0, $count = count($anchors); $i < $count - 1; $i++) {
            $lo = $anchors[$i];
            $hi = $anchors[$i + 1];

            if ($value <= $hi['dist']) {
                $range = $hi['dist'] - $lo['dist'];
                $t = $range > 0 ? ($value - $lo['dist']) / $range : 0.0;
                $percent = (int) round($lo['pct'] + $t * ($hi['pct'] - $lo['pct']));

                return max(0, min(100, $percent));
            }
        }

        return 0;
    }

    public function matchPercentBadgeType(?int $percent): string
    {
        if ($percent === null) {
            return 'neutral';
        }

        if ($percent >= 90) {
            return 'success';
        }

        if ($percent >= 75) {
            return 'amber';
        }

        return 'warning';
    }
}
