<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendWhatsappAttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['masuk', 'pulang'])],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal report wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'type.required' => 'Tipe laporan wajib dipilih.',
            'type.in' => 'Tipe laporan harus masuk atau pulang.',
        ];
    }
}
