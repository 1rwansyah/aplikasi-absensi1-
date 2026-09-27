<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class SyncFaceDescriptorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEmployee() ?? false;
    }

    public function rules(): array
    {
        return [
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['numeric'],
            'faces_detected' => ['required', 'integer', 'in:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'face_descriptor.required' => 'Data wajah dari foto profil wajib diproses.',
            'faces_detected.in' => 'Foto profil harus memuat tepat satu wajah yang jelas.',
        ];
    }

    public function descriptor(): array
    {
        return array_map('floatval', $this->validated('face_descriptor'));
    }

    public function facesDetected(): int
    {
        return (int) $this->validated('faces_detected');
    }
}
