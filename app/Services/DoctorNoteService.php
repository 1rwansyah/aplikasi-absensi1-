<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorNoteService
{
    private const DISK = 'public';

    private const DIRECTORY = 'doctor-notes';

    public function store(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store(self::DIRECTORY, self::DISK);
    }

    public function exists(?string $path): bool
    {
        return filled($path) && Storage::disk(self::DISK)->exists($path);
    }

    public function stream(string $path): StreamedResponse
    {
        if (! $this->exists($path)) {
            abort(404, 'Surat dokter tidak ditemukan.');
        }

        return Storage::disk(self::DISK)->response($path, $this->displayName($path), [
            'Content-Disposition' => 'inline; filename="'.$this->displayName($path).'"',
        ]);
    }

    private function displayName(string $path): string
    {
        return 'surat-dokter-'.basename($path);
    }
}
