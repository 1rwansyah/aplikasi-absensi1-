<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFaceRequest extends FormRequest
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
            'faces_detected' => ['required', 'integer', 'min:0'],
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
