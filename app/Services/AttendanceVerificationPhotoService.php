<?php

namespace App\Services;

use App\DTOs\VerificationPhotoOverlay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceVerificationPhotoService
{
    private const DISK = 'public';

    private const DIRECTORY = 'attendance-verification';

    private const FACE_DIRECTORY = 'face-registration';

    public function __construct(
        private readonly AttendanceVerificationPhotoOverlayService $overlayService,
    ) {}

    public function storeBase64WithOverlay(
        ?string $base64,
        string $prefix,
        VerificationPhotoOverlay $overlay,
    ): ?string {
        if (! filled($base64)) {
            return null;
        }

        $binary = $this->decodeBase64($base64);

        if ($binary === null) {
            return null;
        }

        try {
            $binary = $this->overlayService->apply($binary, $overlay);
        } catch (\Throwable $exception) {
            Log::warning('Gagal menambahkan overlay foto verifikasi absensi.', [
                'prefix' => $prefix,
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->persistBinary($binary, $prefix);
    }

    public function storeBase64(?string $base64, string $prefix): ?string
    {
        if (! filled($base64)) {
            return null;
        }

        $binary = $this->decodeBase64($base64);

        if ($binary === null) {
            return null;
        }

        return $this->persistBinary($binary, $prefix);
    }

    public function storeFaceRegistrationBase64(?string $base64, int $userId): ?string
    {
        if (! filled($base64)) {
            return null;
        }

        $binary = $this->decodeBase64($base64);

        if ($binary === null) {
            return null;
        }

        $path = self::FACE_DIRECTORY.'/user-'.$userId.'.jpg';

        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    public function deleteIfExists(?string $path): void
    {
        if (filled($path) && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return filled($path) && Storage::disk(self::DISK)->exists($path);
    }

    public function url(?string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    public function stream(?string $path)
    {
        if (! $this->exists($path)) {
            abort(404, 'Foto tidak ditemukan.');
        }

        return Storage::disk(self::DISK)->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    private function decodeBase64(string $base64): ?string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $binary = base64_decode($data, true);

        if ($binary === false) {
            return null;
        }

        return $binary;
    }

    private function persistBinary(string $binary, string $prefix): string
    {
        $path = self::DIRECTORY.'/'.$prefix.'-'.Str::uuid().'.jpg';

        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }
}
