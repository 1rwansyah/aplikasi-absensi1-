<?php

namespace Tests\Unit;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Services\AttendanceVerificationPhotoService;
use App\Services\FaceVerificationService;
use App\Support\AppTime;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
        $this->service = app(AttendanceService::class);

        Setting::query()->delete();
        Setting::current()->update([
            'office_latitude' => null,
            'office_longitude' => null,
            'attendance_radius_meters' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_security_can_clock_out_next_morning_using_yesterday_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 07:00:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $securitySchedule->id,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $this->mockClockOutDependencies();

        $result = $this->service->clockOut(
            $user,
            'Laporan pulang security',
            $this->faceDescriptor(),
            1,
            $this->fakePhotoBase64(),
            -6.2,
            106.816666,
        );

        $this->assertTrue($result->success, $result->message);

        $attendance->refresh();
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame('07:00:00', $attendance->clock_out_time);
        $this->assertSame(0.0, $attendance->overtime_hours);
        $this->assertNull($this->service->todayFor($user));
    }

    public function test_regular_cannot_clock_out_without_today_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 18:00:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $regularSchedule = WorkSchedule::where('code', 'regular')->firstOrFail();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $regularSchedule->id,
            'clock_in_time' => '09:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $this->mockClockOutDependencies();

        $result = $this->service->clockOut(
            $user,
            'Laporan pulang',
            $this->faceDescriptor(),
            1,
            $this->fakePhotoBase64(),
            -6.2,
            106.816666,
        );

        $this->assertFalse($result->success);
        $this->assertSame('Absen pulang hanya untuk absensi reguler hari ini.', $result->message);
    }

    public function test_clock_out_target_for_security_finds_yesterday_open_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 07:30:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $securitySchedule->id,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $target = $this->service->clockOutTargetFor($user);

        $this->assertNotNull($target);
        $this->assertSame($attendance->id, $target->id);
        $this->assertSame('2026-06-06', $target->date->toDateString());
    }

    public function test_clock_out_target_ignores_security_attendance_older_than_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-08 07:30:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $securitySchedule->id,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $this->assertNull($this->service->clockOutTargetFor($user));
    }

    public function test_clock_out_context_for_security_enables_button_next_morning(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 07:00:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $securitySchedule->id,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $context = $this->service->clockOutContextFor($user);

        $this->assertTrue($context['canClockOutNow']);
        $this->assertSame($attendance->id, $context['attendance']->id);
        $this->assertSame($attendance->id, $this->service->clockOutTargetFor($user)?->id);
    }

    public function test_clock_out_context_for_security_shows_waiting_before_next_morning(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 06:30:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $securitySchedule->id,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $context = $this->service->clockOutContextFor($user);

        $this->assertFalse($context['canClockOutNow']);
        $this->assertSame('07:00', $context['clockOutOpensAt']);
        $this->assertNotNull($context['attendance']);
        $this->assertNull($this->service->clockOutTargetFor($user));
    }

    public function test_clock_out_context_for_regular_without_today_attendance_is_empty(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 18:00:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();

        $context = $this->service->clockOutContextFor($user);

        $this->assertFalse($context['canClockOutNow']);
        $this->assertNull($context['attendance']);
        $this->assertNull($context['clockOutOpensAt']);
    }

    public function test_night_schedule_pattern_can_clock_out_next_morning(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 07:00:00', AppTime::timezone()));

        $user = $this->createUserWithFaceVerification();
        $nightSchedule = WorkSchedule::updateOrCreate(
            ['code' => 'night'],
            [
                'name' => 'Security Malam',
                'clock_in_start' => '19:00:00',
                'late_limit' => '19:15:00',
                'clock_out_start' => '07:00:00',
                'clock_out_limit' => '07:15:00',
                'is_off' => false,
            ],
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'work_schedule_id' => $nightSchedule->id,
            'clock_in_time' => '19:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $target = $this->service->clockOutTargetFor($user);

        $this->assertNotNull($target);
        $this->assertSame($attendance->id, $target->id);
    }

    private function createUserWithFaceVerification(): User
    {
        Storage::fake('public');
        Storage::disk('public')->put('photos/test.jpg', 'fake-image');

        $user = User::factory()->create();
        Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $user->name,
            'profile_photo' => 'photos/test.jpg',
            'face_descriptor' => json_encode($this->faceDescriptor()),
            'employment_status' => 'active',
            'basic_salary' => 1000000,
        ]);

        return $user->fresh('employee');
    }

    private function mockClockOutDependencies(): void
    {
        $this->mock(FaceVerificationService::class, function ($mock): void {
            $mock->shouldReceive('validateDescriptorPayload')->andReturn(null);
            $mock->shouldReceive('verifyForUser')->andReturn(['success' => true, 'distance' => 0.1]);
        });

        $this->mock(AttendanceVerificationPhotoService::class, function ($mock): void {
            $mock->shouldReceive('storeBase64WithOverlay')->andReturn('attendance-verification/test.jpg');
        });
    }

    private function faceDescriptor(): array
    {
        return array_fill(0, 128, 0.1);
    }

    private function fakePhotoBase64(): string
    {
        return 'data:image/jpeg;base64,'.base64_encode('fake-image');
    }
}
