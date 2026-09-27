<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceType;
use App\Http\Requests\Attendance\Concerns\ValidatesRichTextReport;
use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitLeaveRequest extends FormRequest
{
    use ValidatesRichTextReport;

    public function authorize(): bool
    {
        return $this->user()?->isEmployee() ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                AttendanceType::Sick->value,
                AttendanceType::Permission->value,
            ])],
            'leave_note' => ['required', 'string', 'min:10', 'max:2000'],
            'doctor_note' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Jenis absen wajib dipilih.',
            'leave_note.required' => 'Keterangan wajib diisi.',
            'leave_note.min' => 'Keterangan minimal 10 karakter.',
            'doctor_note.required' => 'Surat dokter / bukti izin wajib diunggah.',
            'doctor_note.mimes' => 'Surat dokter / bukti izin harus berformat PDF, JPG, atau PNG.',
            'doctor_note.max' => 'Ukuran file maksimal 10 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateRichTextReport($validator, 'leave_note', 'Keterangan minimal 10 karakter.');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('leave_note')) {
            $this->merge([
                'leave_note' => RichTextSanitizer::sanitizeHtml($this->input('leave_note')) ?? '',
            ]);
        }
    }

    public function attendanceType(): AttendanceType
    {
        return AttendanceType::from($this->input('type'));
    }
}
