<?php

namespace Tests\Feature;

use App\Exports\PayrollExport;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PayrollExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_download_monthly_payroll_excel(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_code' => 'EMP-001',
            'name' => 'Budi Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Budi Karyawan',
        ]);

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'period_month' => 6,
            'period_year' => 2026,
            'work_days' => 22,
            'present_days' => 20,
            'absent_days' => 2,
            'sick_days' => 0,
            'leave_days' => 0,
            'late_days' => 1,
            'basic_salary' => 5_000_000,
            'total_allowance' => 1_000_000,
            'total_deduction' => 500_000,
            'gross_salary' => 6_000_000,
            'net_salary' => 5_500_000,
            'rounding_amount' => 0,
            'status' => 'draft',
        ]);

        Excel::fake();

        $response = $this->actingAs($admin)->get(route('payrolls.export', [
            'period_month' => 6,
            'period_year' => 2026,
        ]));

        $response->assertOk();

        Excel::assertDownloaded('Penggajian_Juni.xlsx', function (PayrollExport $export) use ($payroll) {
            return $export->query()->get()->contains('id', $payroll->id);
        });
    }

    public function test_guest_cannot_download_payroll_excel(): void
    {
        $this->get(route('payrolls.export'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_cannot_download_payroll_excel(): void
    {
        $employee = User::factory()->employee()->create();
        $employee->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        $this->actingAs($employee)
            ->get(route('payrolls.export'))
            ->assertRedirect(route('login'));
    }
}
