<?php

namespace App\Http\Requests\Attendance;

use App\Http\Requests\Attendance\Concerns\ValidatesAttendanceLocation;
use App\Http\Requests\Attendance\Concerns\ValidatesRichTextReport;
use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClockInRequest extends FormRequest
{
    use ValidatesAttendanceLocation;
    use ValidatesRichTextReport;

    public function authorize(): bool
    {
        return $this->user()?->isEmployee() ?? false;
    }

    public function rules(): array
    {
        return [
            'clock_in_report' => ['required', 'string', 'min:15', 'max:2000'],
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['numeric'],
            'faces_detected' => ['required', 'integer', 'in:1'],
            'verification_photo' => ['required', 'string'],
            ...$this->locationRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in_report.required' => 'Laporan rencana kerja hari ini wajib diisi.',
            'clock_in_report.min' => 'Laporan minimal 15 karakter.',
            ...$this->locationMessages(),
            'face_descriptor.required' => 'Verifikasi wajah wajib dilakukan.',
            'faces_detected.in' => 'Hanya satu wajah yang diperbolehkan saat absensi.',
            'verification_photo.required' => 'Foto verifikasi wajah wajib dikirim.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateRichTextReport($validator, 'clock_in_report', 'Laporan minimal 15 karakter.', 15);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('clock_in_report')) {
            $this->merge([
                'clock_in_report' => RichTextSanitizer::sanitizeHtml($this->input('clock_in_report')) ?? '',
            ]);
        }
    }

    public function faceDescriptor(): array
    {
        return array_map('floatval', $this->validated('face_descriptor'));
    }

    public function facesDetected(): int
    {
        return (int) $this->validated('faces_detected');
    }
}
