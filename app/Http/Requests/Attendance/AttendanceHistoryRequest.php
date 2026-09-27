<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Support\AppTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceHistoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->hasAny(['date', 'month', 'type', 'status'])) {
            $this->merge(['month' => AppTime::now()->format('Y-m')]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isEmployee() ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'type' => ['nullable', Rule::enum(AttendanceType::class)],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
        ];
    }

    public function selectedDate(): ?string
    {
        $date = $this->validated('date') ?? null;

        return filled($date) ? $date : null;
    }

    public function month(): ?string
    {
        $month = $this->validated('month') ?? null;

        return filled($month) ? $month : null;
    }

    public function type(): ?AttendanceType
    {
        $value = $this->validated('type');

        return $value ? AttendanceType::from($value) : null;
    }

    public function status(): ?AttendanceStatus
    {
        $value = $this->validated('status');

        return $value ? AttendanceStatus::from($value) : null;
    }
}
