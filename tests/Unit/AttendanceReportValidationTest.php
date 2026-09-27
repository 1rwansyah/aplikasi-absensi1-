<?php

namespace Tests\Unit;

use App\Http\Requests\Attendance\ClockInRequest;
use App\Http\Requests\Attendance\ClockOutRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Tests\TestCase;

class AttendanceReportValidationTest extends TestCase
{
    public function test_clock_in_and_clock_out_reject_reports_below_fifteen_plain_text_characters(): void
    {
        foreach ($this->reportRequests('<p>12345678901234</p>') as [$request, $field]) {
            $validator = $this->validatorFor($request);

            $this->assertTrue($validator->fails());
            $this->assertSame(
                ['Laporan minimal 15 karakter.'],
                $validator->errors()->get($field),
            );
        }
    }

    public function test_clock_in_and_clock_out_accept_reports_with_exactly_fifteen_plain_text_characters(): void
    {
        foreach ($this->reportRequests('<p>123456789012345</p>') as [$request, $field]) {
            $validator = $this->validatorFor($request);

            $this->assertFalse($validator->fails(), implode(' ', $validator->errors()->all()));
            $this->assertEmpty($validator->errors()->get($field));
        }
    }

    /**
     * @return array<int, array{FormRequest, string}>
     */
    private function reportRequests(string $report): array
    {
        return [
            [
                ClockInRequest::create('/', 'POST', $this->validPayload('clock_in_report', $report)),
                'clock_in_report',
            ],
            [
                ClockOutRequest::create('/', 'POST', $this->validPayload('clock_out_report', $report)),
                'clock_out_report',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(string $field, string $report): array
    {
        return [
            $field => $report,
            'face_descriptor' => array_fill(0, 128, 0.1),
            'faces_detected' => 1,
            'verification_photo' => 'data:image/jpeg;base64,dGVzdA==',
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ];
    }

    private function validatorFor(FormRequest $request): Validator
    {
        $validator = validator($request->all(), $request->rules(), $request->messages());
        $request->withValidator($validator);

        return $validator;
    }
}
