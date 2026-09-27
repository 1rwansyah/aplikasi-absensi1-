<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollDetailChange;
use App\Models\Role;
use App\Models\User;
use App\Services\PayrollSnapshotService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollResetToSystemTest extends TestCase
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
            'name' => 'Potongan Alfa',
            'type' => 'deduction',
            'amount' => 500_000,
        ]);

        PayrollSnapshotService::capture($this->payroll->fresh('details'));
    }

    public function test_reset_restores_snapshot_after_edit_and_delete(): void
    {
        $transport = PayrollDetail::where('name', 'Transport')->firstOrFail();
        $alfa = PayrollDetail::where('name', 'Potongan Alfa')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('payroll-details.update', $transport), [
            'name' => 'Transport Revisi',
            'amount' => 900_000,
        ]);

        $this->actingAs($this->admin)->delete(route('payroll-details.destroy', $alfa));

        $this->actingAs($this->admin)->post(route('payrolls.adjustments.store', $this->payroll), [
            'name' => 'Bonus',
            'type' => 'allowance',
            'amount' => 200_000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('payrolls.reset-to-system', $this->payroll));

        $response->assertRedirect(route('payrolls.show', $this->payroll));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payroll_details', [
            'payroll_id' => $this->payroll->id,
            'name' => 'Transport',
            'amount' => 500_000,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('payroll_details', [
            'payroll_id' => $this->payroll->id,
            'name' => 'Potongan Alfa',
            'amount' => 500_000,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseMissing('payroll_details', [
            'name' => 'Bonus',
            'deleted_at' => null,
        ]);

        $this->payroll->refresh();
        $this->assertSame(5_500_000.0, (float) $this->payroll->net_salary);

        $this->assertSame(
            0,
            PayrollDetailChange::where('payroll_id', $this->payroll->id)->undoable()->count()
        );
    }

    public function test_reset_without_snapshot_returns_error(): void
    {
        $payroll = Payroll::create([
            'employee_id' => Employee::create([
                'user_id' => User::factory()->create()->id,
                'employee_code' => 'EMP-002',
                'name' => 'Siti',
                'employment_status' => 'active',
                'basic_salary' => 4_000_000,
            ])->id,
            'period_month' => 7,
            'period_year' => 2026,
            'work_days' => 22,
            'present_days' => 20,
            'absent_days' => 2,
            'basic_salary' => 4_000_000,
            'total_allowance' => 0,
            'total_deduction' => 0,
            'gross_salary' => 4_000_000,
            'net_salary' => 4_000_000,
            'rounding_amount' => 0,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)->post(route('payrolls.reset-to-system', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_reset_paid_payroll(): void
    {
        $detail = PayrollDetail::where('name', 'Transport')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('payroll-details.update', $detail), [
            'name' => 'Transport',
            'amount' => 700_000,
        ]);

        $this->payroll->update(['status' => 'paid', 'paid_at' => now()]);

        $response = $this->actingAs($this->admin)->post(route('payrolls.reset-to-system', $this->payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
