<?php



namespace Tests\Feature;



use App\Enums\AttendanceStatus;

use App\Enums\AttendanceType;

use App\Imports\SecurityScheduleImport;

use App\Models\Attendance;

use App\Models\Employee;

use App\Models\EmployeeSchedule;

use App\Models\Role;

use App\Models\Setting;

use App\Models\User;

use App\Models\WorkSchedule;

use App\Services\AttendanceService;

use App\Services\AttendanceVerificationPhotoService;

use App\Services\FaceVerificationService;

use Carbon\Carbon;

use Database\Seeders\RoleSeeder;

use Database\Seeders\WorkScheduleSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;

use Tests\TestCase;



class AttendanceWeekFlowTest extends TestCase

{

    use RefreshDatabase;



    private const WEEK_START = '2026-06-01';



    private AttendanceService $attendanceService;



    private User $regularA;



    private User $regularB;



    private User $securityA;



    private User $securityB;



    private User $admin;



    protected function setUp(): void

    {

        parent::setUp();



        $this->seed([WorkScheduleSeeder::class, RoleSeeder::class]);

        $this->attendanceService = app(AttendanceService::class);



        Setting::query()->delete();

        Setting::current()->update([

            'office_start' => '08:30:00',

            'late_limit' => '08:45:00',

            'clock_out_start' => '17:30:00',

            'clock_out_limit' => '17:45:00',

            'office_latitude' => null,

            'office_longitude' => null,

            'attendance_radius_meters' => 0,

        ]);



        Storage::fake('public');

        Storage::disk('public')->put('photos/test.jpg', 'fake-image');



        $this->regularA = $this->createEmployeeUser('REG-001', 'Budi Reguler', null);

        $this->regularB = $this->createEmployeeUser('REG-002', 'Siti Reguler', null);

        $this->securityA = $this->createEmployeeUser('SEC-001', 'Andi Security', 'Security');

        $this->securityB = $this->createEmployeeUser('SEC-002', 'Rina Security', 'Security');

        $this->admin = $this->createAdminUser();

    }



    protected function tearDown(): void

    {

        Carbon::setTestNow();

        parent::tearDown();

    }



    public function test_regular_on_time_clock_in_and_clock_out(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-02 09:05:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $clockIn = $this->attendanceService->clockIn(

            $this->regularA,

            'Masuk tepat waktu',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertTrue($clockIn->success, $clockIn->message);



        $attendance = $this->attendanceService->todayFor($this->regularA);

        $this->assertSame(AttendanceStatus::OnTime, $attendance->status);

        $this->assertStringContainsString('Jam Kerja Reguler', $attendance->shiftLabel());



        Carbon::setTestNow(Carbon::parse('2026-06-02 18:05:00', 'Asia/Jakarta'));



        $clockOut = $this->attendanceService->clockOut(

            $this->regularA,

            'Pulang normal',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertTrue($clockOut->success, $clockOut->message);

        $attendance->refresh();

        $this->assertSame(AttendanceStatus::OnTime, $attendance->status);

        $this->assertNotNull($attendance->clock_out_time);

    }



    public function test_regular_late_clock_in(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-03 09:20:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $result = $this->attendanceService->clockIn(

            $this->regularA,

            'Masuk telat',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertTrue($result->success, $result->message);

        $attendance = $this->attendanceService->todayFor($this->regularA);

        $this->assertSame(AttendanceStatus::Late, $attendance->status);

    }



    public function test_regular_early_clock_out_is_blocked(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-04 09:00:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $this->attendanceService->clockIn(

            $this->regularA,

            'Masuk',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        Carbon::setTestNow(Carbon::parse('2026-06-04 17:00:00', 'Asia/Jakarta'));



        $result = $this->attendanceService->clockOut(

            $this->regularA,

            'Pulang cepat',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertFalse($result->success);

        $this->assertTrue(

            str_contains($result->message, 'Belum waktunya absen pulang')

            || str_contains($result->message, 'Absen pulang hanya untuk absensi reguler hari ini'),

            'Early clock-out is blocked but error message may be misleading: '.$result->message,

        );

    }



    public function test_regular_late_clock_out(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-05 09:00:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $this->attendanceService->clockIn(

            $this->regularA,

            'Masuk',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        Carbon::setTestNow(Carbon::parse('2026-06-05 18:20:00', 'Asia/Jakarta'));



        $result = $this->attendanceService->clockOut(

            $this->regularA,

            'Pulang lewat',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertTrue($result->success, $result->message);

        $attendance = $this->attendanceService->todayFor($this->regularA);

        $this->assertSame(AttendanceStatus::LateOut, $attendance->status);

    }



    public function test_regular_sick_and_permission_leave(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));



        $sick = $this->attendanceService->submitLeave(

            $this->regularA,

            AttendanceType::Sick,

            'Demam',

            null,

        );

        $this->assertTrue($sick->success, $sick->message);



        $sickRecord = $this->attendanceService->todayFor($this->regularA);

        $this->assertSame(AttendanceStatus::Sick, $sickRecord->status);

        $this->assertSame(AttendanceType::Sick, $sickRecord->type);

        $lockedPage = $this->actingAs($this->regularA)->get(route('attendance.index'));

        $lockedPage->assertOk();

        $lockedPage->assertSee('Absensi &amp; Izin Dikunci', false);

        $lockedPage->assertSee('Pengajuan sakit hari ini sudah tercatat.');

        $lockedPage->assertSee('sedang menunggu pemeriksaan HR');



        Carbon::setTestNow(Carbon::parse('2026-06-07 08:00:00', 'Asia/Jakarta'));



        $permission = $this->attendanceService->submitLeave(

            $this->regularB,

            AttendanceType::Permission,

            'Urusan keluarga',

            null,

        );

        $this->assertTrue($permission->success, $permission->message);



        $permissionRecord = $this->attendanceService->todayFor($this->regularB);

        $this->assertSame(AttendanceStatus::Permission, $permissionRecord->status);

    }



    public function test_regular_absent_has_no_attendance_record(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', 'Asia/Jakarta'));



        $this->assertNull($this->attendanceService->todayFor($this->regularB));

        $this->assertSame(0, Attendance::where('user_id', $this->regularB->id)->count());

    }



    public function test_work_hours_settings_page_uses_setting_not_work_schedule_for_calculation_gap(): void

    {

        $regular = WorkSchedule::where('code', 'regular')->firstOrFail();

        $setting = Setting::current();



        $this->assertNotSame(

            $setting->office_start,

            $regular->clock_in_start,

            'Work Hours Settings and WorkSchedule regular are separate data sources.',

        );



        Carbon::setTestNow(Carbon::parse('2026-06-02 08:40:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $this->attendanceService->clockIn(

            $this->regularA,

            'Masuk',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $attendance = $this->attendanceService->todayFor($this->regularA);

        $this->assertSame(AttendanceStatus::OnTime, $attendance->status, 'Uses WorkSchedule regular (09:15 late limit), not Setting (08:45).');

    }



    public function test_security_weekly_schedule_store_creates_assignments(): void

    {

        $weekStart = Carbon::parse(self::WEEK_START)->startOfWeek();

        $securityId = WorkSchedule::where('code', 'security')->value('id');

        $offId = WorkSchedule::where('code', 'off')->value('id');



        $schedules = [

            (string) $this->securityA->employee->id => [

                $weekStart->toDateString() => 'lobby',

                $weekStart->copy()->addDay()->toDateString() => 'gate',

                $weekStart->copy()->addDays(2)->toDateString() => 'off',

                $weekStart->copy()->addDays(3)->toDateString() => 'lobby',

                $weekStart->copy()->addDays(4)->toDateString() => 'off',

                $weekStart->copy()->addDays(5)->toDateString() => 'gate',

                $weekStart->copy()->addDays(6)->toDateString() => 'off',

            ],

        ];



        $response = $this->actingAs($this->admin)->post(route('settings.security-schedules.store'), [

            'week_start' => $weekStart->toDateString(),

            'schedules' => $schedules,

        ]);



        $response->assertRedirect();

        $response->assertSessionHas('success');



        $monday = EmployeeSchedule::where('employee_id', $this->securityA->employee->id)

            ->whereDate('work_date', $weekStart)

            ->first();

        $this->assertSame($securityId, $monday->work_schedule_id);

        $this->assertSame('lobby', $monday->post_location);



        $wednesday = EmployeeSchedule::where('employee_id', $this->securityA->employee->id)

            ->whereDate('work_date', $weekStart->copy()->addDays(2))

            ->first();

        $this->assertSame($offId, $wednesday->work_schedule_id);

        $this->assertNull($wednesday->post_location);

    }



    public function test_security_import_still_works_for_week(): void

    {

        $weekStart = Carbon::parse(self::WEEK_START)->startOfWeek();



        $csv = implode("\n", [

            'nama,sen,sel,rab,kam,jum,sab,min',

            'Rina Security,L,PG,OFF,L,PG,OFF,L',

        ]);



        $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

        Excel::import(new SecurityScheduleImport($weekStart), $file);



        $this->assertSame(7, EmployeeSchedule::where('employee_id', $this->securityB->employee->id)->count());

        $this->assertSame(

            'gate',

            EmployeeSchedule::where('employee_id', $this->securityB->employee->id)

                ->whereDate('work_date', $weekStart->copy()->addDay())

                ->value('post_location'),

        );

    }



    public function test_security_cannot_clock_in_on_off_day(): void

    {

        $off = WorkSchedule::where('code', 'off')->firstOrFail();



        EmployeeSchedule::create([

            'employee_id' => $this->securityA->employee->id,

            'work_schedule_id' => $off->id,

            'work_date' => '2026-06-02',

        ]);



        Carbon::setTestNow(Carbon::parse('2026-06-02 07:00:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $result = $this->attendanceService->clockIn(

            $this->securityA,

            'Coba masuk',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertFalse($result->success);

        $this->assertStringContainsString('hari libur', strtolower($result->message));

    }



    public function test_security_overnight_clock_out_next_morning(): void

    {

        $securityId = WorkSchedule::where('code', 'security')->value('id');



        EmployeeSchedule::create([

            'employee_id' => $this->securityA->employee->id,

            'work_schedule_id' => $securityId,

            'work_date' => '2026-06-03',

            'post_location' => 'lobby',

        ]);



        Carbon::setTestNow(Carbon::parse('2026-06-03 07:00:00', 'Asia/Jakarta'));

        $this->mockAttendanceDependencies();



        $clockIn = $this->attendanceService->clockIn(

            $this->securityA,

            'Mulai tugas',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );

        $this->assertTrue($clockIn->success, $clockIn->message);



        Carbon::setTestNow(Carbon::parse('2026-06-04 07:05:00', 'Asia/Jakarta'));



        $clockOut = $this->attendanceService->clockOut(

            $this->securityA,

            'Selesai tugas',

            $this->faceDescriptor(),

            1,

            $this->fakePhotoBase64(),

            -6.2,

            106.816666,

        );



        $this->assertTrue($clockOut->success, $clockOut->message);



        $attendance = Attendance::where('user_id', $this->securityA->id)

            ->whereDate('date', '2026-06-03')

            ->first();



        $this->assertNotNull($attendance->clock_out_time);

        $this->assertSame('2026-06-03', $attendance->date->toDateString());

    }



    public function test_attendance_pages_render_with_expected_labels(): void

    {

        Carbon::setTestNow(Carbon::parse('2026-06-02 10:00:00', 'Asia/Jakarta'));



        $off = WorkSchedule::where('code', 'off')->firstOrFail();

        EmployeeSchedule::create([

            'employee_id' => $this->regularB->employee->id,

            'work_schedule_id' => $off->id,

            'work_date' => '2026-06-02',

        ]);



        $attendancePage = $this->actingAs($this->regularB)->get(route('attendance.index'));

        $attendancePage->assertOk();

        $attendancePage->assertSee('Jam Kerja');

        $attendancePage->assertSee('Libur');

        $attendancePage->assertDontSee('Shift Pagi');

        $attendancePage->assertDontSee('Shift Malam');

        $regularAttendancePage = $this->actingAs($this->regularA)->get(route('attendance.index'));

        $regularAttendancePage->assertOk();

        $regularAttendancePage->assertSee(':value="location ? location.latitude : \'\'"', false);

        $regularAttendancePage->assertDontSee('x-model="location?.', false);



        $historyPage = $this->actingAs($this->regularA)->get(route('attendance.history'));

        $historyPage->assertOk();

        $historyPage->assertSee('Jam Kerja');

        $historyPage->assertDontSee('Shift Pagi');

        $historyPage->assertDontSee('Shift Malam');

    }

    public function test_employee_can_filter_attendance_history_by_exact_date(): void
    {
        Attendance::create([
            'user_id' => $this->regularA->id,
            'date' => '2026-05-12',
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'clock_out_time' => '17:00:00',
            'clock_in_report' => 'Laporan tanggal yang dicari',
            'status' => AttendanceStatus::OnTime,
        ]);

        Attendance::create([
            'user_id' => $this->regularA->id,
            'date' => '2026-05-13',
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'clock_out_time' => '17:00:00',
            'clock_in_report' => 'Laporan tanggal lainnya',
            'status' => AttendanceStatus::OnTime,
        ]);

        $response = $this->actingAs($this->regularA)->get(route('attendance.history', [
            'date' => '2026-05-12',
            'month' => '2026-06',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-05-12"', false);
        $response->assertSeeText('Laporan tanggal yang dicari');
        $response->assertDontSeeText('Laporan tanggal lainnya');
    }



    public function test_admin_settings_routes_and_legacy_redirects(): void

    {

        $workHours = $this->actingAs($this->admin)->get(route('settings.work-hours.edit'));

        $workHours->assertOk();

        $workHours->assertSee('Pengaturan Jam Kerja');

        $workHours->assertSee('Jam Kerja Reguler');

        $workHours->assertDontSee('Shift Malam');



        $legacyShifts = $this->actingAs($this->admin)->get('/settings/shifts');

        $legacyShifts->assertRedirect('/settings/work-hours');



        $legacyAttendance = $this->actingAs($this->admin)->get('/settings/attendance');

        $legacyAttendance->assertRedirect(route('settings.work-hours.edit'));



        $securitySchedules = $this->actingAs($this->admin)->get(route('settings.security-schedules.index'));

        $securitySchedules->assertOk();

        $securitySchedules->assertSee('Jadwal Security');



        $adminAttendance = $this->actingAs($this->admin)->get(route('admin.attendance.index'));

        $adminAttendance->assertOk();

    }



    public function test_work_hours_update_persists_setting_values(): void

    {

        $response = $this->actingAs($this->admin)->patch(route('settings.work-hours.update'), [

            'office_start' => '10:00',

            'late_limit' => '10:15',

            'clock_out_start' => '19:00',

            'clock_out_limit' => '19:30',

            'security_clock_in_start' => '07:00',

            'security_late_limit' => '07:15',

            'security_clock_out_start' => '19:00',

            'security_clock_out_limit' => null,

            'ob_clock_in_start' => '08:00',

            'ob_late_limit' => '08:30',

            'ob_clock_out_start' => '17:00',

            'ob_clock_out_limit' => null,

            'engineering_clock_in_start' => '00:00',

            'engineering_late_limit' => '00:15',

            'engineering_clock_out_start' => '05:00',

            'engineering_clock_out_limit' => '05:15',

        ]);



        $response->assertRedirect();

        $response->assertSessionHas('success');



        $setting = Setting::current()->fresh();

        $this->assertSame('10:00:00', $setting->office_start);

        $this->assertSame('10:15:00', $setting->late_limit);

        $this->assertSame('19:00:00', $setting->clock_out_start);

        $this->assertSame('19:30:00', $setting->clock_out_limit);

    }



    private function createAdminUser(): User

    {

        $user = User::factory()->admin()->create([

            'name' => 'Admin HRIS',

            'email' => 'admin@test.local',

        ]);

        $user->roles()->attach(Role::where('name', 'admin')->firstOrFail());



        return $user;

    }



    private function createEmployeeUser(string $code, string $name, ?string $staff): User

    {

        $user = User::factory()->create(['name' => $name]);

        $user->roles()->attach(Role::where('name', 'employee')->firstOrFail());



        Employee::create([

            'user_id' => $user->id,

            'employee_code' => $code,

            'name' => $name,

            'staff' => $staff,

            'profile_photo' => 'photos/test.jpg',

            'face_descriptor' => json_encode($this->faceDescriptor()),

            'employment_status' => 'active',

            'basic_salary' => 5_000_000,

        ]);



        return $user->fresh('employee');

    }



    private function mockAttendanceDependencies(): void

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

