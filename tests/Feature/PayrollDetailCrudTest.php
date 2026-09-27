<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollDetailCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Payroll $payroll;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_code' => 'EMP-001',
            'name' => 'Budi Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->payroll = Payroll::create([
            'employee_id' => $employee->id,
            'period_month' => 6,
            'period_year' => 2026,
            'work_days' => 22,
            'present_days' => 20,
            'absent_days' => 2,
            'basic_salary' => 5_000_000,
            'total_allowance' => 1_000_000,
            'total_deduction' => 500_000,
            'gross_salary' => 6_000_000,
            'net_salary' => 5_500_000,
            'rounding_amount' => 0,
            'status' => 'draft',
        ]);

        PayrollDetail::create([
            'payroll_id' => $this->payroll->id,
            'name' => 'Gaji Pokok',
            'type' => 'salary',
            'amount' => 5_000_000,
        ]);

        PayrollDetail::create([
            'payroll_id' => $this->payroll->id,
            'name' => 'Transport',
            'type' => 'allowance',
            'amount' => 500_000,
        ]);

        PayrollDetail::create([
            'payroll_id' => $this->payroll->id,
            'name' => 'Uang Makan',
            'type' => 'allowance',
            'amount' => 500_000,
        ]);

        PayrollDetail::create([
            'payroll_id' => $this->payroll->id,
            'name' => 'BPJS Kesehatan',
            'type' => 'deduction',
            'amount' => 300_000,
        ]);

        PayrollDetail::create([
            'payroll_id' => $this->payroll->id,
            'name' => 'Potongan Alfa',
            'type' => 'deduction',
            'amount' => 200_000,
        ]);
    }

    public function test_admin_can_update_allowance_item_and_recalculates_totals(): void
    {
        $detail = PayrollDetail::where('name', 'Transport')->firstOrFail();

        $response = $this->actingAs($this->admin)->patch(route('payroll-details.update', $detail), [
            'name' => 'Transport (Revisi)',
            'amount' => 750_000,
            'notes' => 'Disesuaikan manual',
        ]);

        $response->assertRedirect(route('payrolls.show', $this->payroll));
        $response->assertSessionHas('success');

        $detail->refresh();
        $this->payroll->refresh();

        $this->assertSame('Transport (Revisi)', $detail->name);
        $this->assertSame(750_000.0, (float) $detail->amount);
        $this->assertTrue($detail->is_adjustment);
        $this->assertSame(1_250_000.0, (float) $this->payroll->total_allowance);
        $this->assertSame(6_250_000.0, (float) $this->payroll->gross_salary);
        $this->assertSame(5_750_000.0, (float) $this->payroll->net_salary);
    }

    public function test_admin_can_delete_deduction_item_and_recalculates_totals(): void
    {
        $detail = PayrollDetail::where('name', 'Potongan Alfa')->firstOrFail();

        $response = $this->actingAs($this->admin)->delete(route('payroll-details.destroy', $detail));

        $response->assertRedirect(route('payrolls.show', $this->payroll));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('payroll_details', ['id' => $detail->id]);

        $this->payroll->refresh();

        $this->assertSame(300_000.0, (float) $this->payroll->total_deduction);
        $this->assertSame(5_700_000.0, (float) $this->payroll->net_salary);
    }

    public function test_cannot_edit_salary_row(): void
    {
        $detail = PayrollDetail::where('name', 'Gaji Pokok')->firstOrFail();

        $response = $this->actingAs($this->admin)->patch(route('payroll-details.update', $detail), [
            'name' => 'Gaji Pokok',
            'amount' => 6_000_000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_modify_paid_payroll_items(): void
    {
        $this->payroll->update(['status' => 'paid', 'paid_at' => now()]);

        $detail = PayrollDetail::where('name', 'Transport')->firstOrFail();

        $response = $this->actingAs($this->admin)->delete(route('payroll-details.destroy', $detail));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('payroll_details', ['id' => $detail->id]);
    }
}
