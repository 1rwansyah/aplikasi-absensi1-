<?php

namespace Tests\Unit;

use App\Exceptions\SecurityScheduleImportException;
use App\Imports\SecurityScheduleImport;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SecurityScheduleImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
    }

    public function test_imports_security_schedule_from_excel(): void
    {
        $employee = $this->createEmployee('Alfie Syauqi');
        $weekStart = Carbon::parse('2026-06-02')->startOfWeek();

        $csv = implode("\n", [
            'nama,sen,sel,rab,kam,jum,sab,min',
            'Alfie Syauqi,L,OFF,PG,OFF,L,PG,OFF',
        ]);

        $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

        Excel::import(new SecurityScheduleImport($weekStart), $file);

        $securityId = WorkSchedule::where('code', 'security')->value('id');
        $offId = WorkSchedule::where('code', 'off')->value('id');

        $monday = $weekStart->toDateString();
        $tuesday = $weekStart->copy()->addDay()->toDateString();
        $wednesday = $weekStart->copy()->addDays(2)->toDateString();
        $saturday = $weekStart->copy()->addDays(5)->toDateString();

        $this->assertSame(
            'lobby',
            EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', $monday)->value('post_location'),
        );
        $this->assertSame($offId, EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', $tuesday)->value('work_schedule_id'));
        $this->assertSame(
            'gate',
            EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', $wednesday)->value('post_location'),
        );
        $this->assertSame($securityId, EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', $saturday)->value('work_schedule_id'));
        $this->assertSame(7, EmployeeSchedule::where('employee_id', $employee->id)->count());
    }

    public function test_reports_row_errors_for_unknown_employee_and_invalid_value(): void
    {
        $weekStart = Carbon::parse('2026-06-02')->startOfWeek();

        $csv = implode("\n", [
            'nama,sen,sel,rab,kam,jum,sab,min',
            'Tidak Ada,L,XX,PG,OFF,L,PG,OFF',
        ]);

        $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

        try {
            Excel::import(new SecurityScheduleImport($weekStart), $file);
            $this->fail('Expected SecurityScheduleImportException was not thrown.');
        } catch (SecurityScheduleImportException $exception) {
            $this->assertStringContainsString('Baris 2: karyawan "Tidak Ada" tidak ditemukan.', $exception->errors[0]);
            $this->assertTrue(
                collect($exception->errors)->contains(
                    fn (string $message) => str_contains($message, 'Baris 2:') && str_contains($message, 'sel') && str_contains($message, 'XX'),
                ),
            );
        }

        $this->assertSame(0, EmployeeSchedule::count());
    }

    private function createEmployee(string $name): Employee
    {
        $user = User::factory()->create(['name' => $name]);

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $name,
            'staff' => 'Security',
            'employment_status' => 'active',
            'basic_salary' => 1000000,
        ]);
    }
}
