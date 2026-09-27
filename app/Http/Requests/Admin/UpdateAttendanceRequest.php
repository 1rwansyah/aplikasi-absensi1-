<?php

namespace App\Http\Requests\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Http\Requests\Attendance\Concerns\ValidatesRichTextReport;
use App\Models\Attendance;
use App\Services\AdminAttendanceService;
use App\Support\AppTime;
use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateAttendanceRequest extends FormRequest
{
    use ValidatesRichTextReport;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $isRegular = $this->input('type') === AttendanceType::Regular->value;

        return [
            'clock_in_date' => ['required', 'date'],
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'clock_in_time' => [
                Rule::requiredIf($isRegular),
                'nullable',
                'date_format:H:i',
            ],
            'clock_out_date' => [
                'nullable',
                'date',
                'required_with:clock_out_time',
            ],
            'clock_out_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:clock_out_date',
            ],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'clock_in_report' => ['nullable', 'string', 'max:2000'],
            'clock_out_report' => ['nullable', 'string', 'max:2000'],
            'leave_note' => ['nullable', 'string', 'max:2000'],
            'manual_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in_date.required' => 'Tanggal masuk wajib diisi.',
            'type.required' => 'Tipe absensi wajib dipilih.',
            'clock_in_time.required' => 'Jam masuk wajib diisi untuk absensi reguler.',
            'clock_out_date.required_with' => 'Tanggal pulang wajib diisi jika jam pulang diisi.',
            'clock_out_time.required_with' => 'Jam pulang wajib diisi jika tanggal pulang diisi.',
            'status.required' => 'Status absensi wajib dipilih.',
            'manual_reason.required' => 'Alasan koreksi manual wajib diisi.',
            'manual_reason.min' => 'Alasan koreksi manual minimal 5 karakter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->filled('clock_in_report')) {
            $this->validateRichTextReport($validator, 'clock_in_report', 'Laporan masuk minimal 10 karakter.');
        }

        if ($this->filled('clock_out_report')) {
            $this->validateRichTextReport($validator, 'clock_out_report', 'Laporan pulang minimal 10 karakter.');
        }

        if ($this->filled('leave_note')) {
            $this->validateRichTextReport($validator, 'leave_note', 'Keterangan izin/sakit minimal 10 karakter.');
        }

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Attendance $attendance */
            $attendance = $this->route('attendance');
            $type = AttendanceType::from($this->input('type'));
            $status = AttendanceStatus::from($this->input('status'));
            $clockInDate = $this->input('clock_in_date');

            if (Attendance::query()
                ->where('user_id', $attendance->user_id)
                ->whereDate('date', $clockInDate)
                ->whereKeyNot($attendance->id)
                ->exists()) {
                $validator->errors()->add('clock_in_date', 'Karyawan sudah memiliki absensi pada tanggal masuk tersebut.');

                return;
            }

            if ($type === AttendanceType::Sick && $status !== AttendanceStatus::Sick) {
                $validator->errors()->add('status', 'Status sakit wajib dipilih untuk tipe sakit.');
            }

            if ($type === AttendanceType::Permission && $status !== AttendanceStatus::Permission) {
                $validator->errors()->add('status', 'Status izin wajib dipilih untuk tipe izin.');
            }

            if ($type !== AttendanceType::Regular) {
                return;
            }

            try {
                app(AdminAttendanceService::class)->assertManualClockOutAfterClockIn(
                    Carbon::parse($clockInDate, AppTime::timezone())->startOfDay(),
                    $this->normalizedTime('clock_in_time'),
                    $this->filled('clock_out_date')
                        ? Carbon::parse($this->input('clock_out_date'), AppTime::timezone())->startOfDay()
                        : null,
                    $this->normalizedTime('clock_out_time'),
                );
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['clock_in_report', 'clock_out_report'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = RichTextSanitizer::sanitizeHtml($this->input($field));
            }
        }

        if ($this->has('leave_note')) {
            $merge['leave_note'] = RichTextSanitizer::sanitizeHtml($this->input('leave_note'));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceAttributes(): array
    {
        return [
            'clock_in_date' => $this->validated('clock_in_date'),
            'type' => $this->validated('type'),
            'clock_in_time' => $this->normalizedTime('clock_in_time'),
            'clock_out_date' => $this->validated('clock_out_date'),
            'clock_out_time' => $this->normalizedTime('clock_out_time'),
            'status' => $this->validated('status'),
            'clock_in_report' => $this->validated('clock_in_report'),
            'clock_out_report' => $this->validated('clock_out_report'),
            'leave_note' => $this->validated('leave_note'),
        ];
    }

    public function manualReason(): string
    {
        return trim($this->validated('manual_reason'));
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        /** @var Attendance $attendance */
        $attendance = $this->route('attendance');

        throw new HttpResponseException(
            redirect()->route('admin.attendance.index', [
                'date' => $this->input('clock_in_date', $attendance->date->toDateString()),
                'edit' => $attendance->id,
            ])->withInput()->withErrors($validator)
        );
    }

    private function normalizedTime(string $field): ?string
    {
        $value = $this->input($field);

        if ($value === null || $value === '') {
            return null;
        }

        return strlen((string) $value) === 5
            ? $value.':00'
            : (string) $value;
    }
}
