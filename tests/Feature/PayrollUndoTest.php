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

class PayrollUndoTest extends TestCase
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

    public function test_undo_reverts_last_update(): void
    {
        $detail = PayrollDetail::where('name', 'Transport')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('payroll-details.update', $detail), [
            'name' => 'Transport Baru',
            'amount' => 800_000,
            'notes' => 'Revisi',
        ]);

        $response = $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $response->assertRedirect(route('payrolls.show', $this->payroll));
        $response->assertSessionHas('success');

        $detail->refresh();
        $this->assertSame('Transport', $detail->name);
        $this->assertSame(500_000.0, (float) $detail->amount);
        $this->assertFalse($detail->is_adjustment);

        $this->payroll->refresh();
        $this->assertSame(5_500_000.0, (float) $this->payroll->net_salary);
    }

    public function test_undo_reverts_last_delete_via_soft_delete_restore(): void
    {
        $detail = PayrollDetail::where('name', 'Potongan Alfa')->firstOrFail();

        $this->actingAs($this->admin)->delete(route('payroll-details.destroy', $detail));

        $this->assertSoftDeleted('payroll_details', ['id' => $detail->id]);

        $response = $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $response->assertRedirect(route('payrolls.show', $this->payroll));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payroll_details', [
            'id' => $detail->id,
            'name' => 'Potongan Alfa',
            'deleted_at' => null,
        ]);
    }

    public function test_undo_reverts_last_create(): void
    {
        $this->actingAs($this->admin)->post(route('payrolls.adjustments.store', $this->payroll), [
            'name' => 'Bonus Proyek',
            'type' => 'allowance',
            'amount' => 250_000,
        ]);

        $this->assertDatabaseHas('payroll_details', ['name' => 'Bonus Proyek']);

        $response = $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $response->assertRedirect(route('payrolls.show', $this->payroll));
        $this->assertDatabaseMissing('payroll_details', ['name' => 'Bonus Proyek', 'deleted_at' => null]);
    }

    public function test_undo_without_changes_returns_error(): void
    {
        $response = $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_undo_is_lifo_for_multiple_changes(): void
    {
        $detail = PayrollDetail::where('name', 'Transport')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('payroll-details.update', $detail), [
            'name' => 'Transport',
            'amount' => 600_000,
        ]);

        $this->actingAs($this->admin)->post(route('payrolls.adjustments.store', $this->payroll), [
            'name' => 'Bonus',
            'type' => 'allowance',
            'amount' => 100_000,
        ]);

        $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $this->assertDatabaseMissing('payroll_details', ['name' => 'Bonus', 'deleted_at' => null]);
        $this->assertDatabaseHas('payroll_details', ['name' => 'Transport', 'amount' => 600_000]);

        $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $detail->refresh();
        $this->assertSame(500_000.0, (float) $detail->amount);
    }

    public function test_cannot_undo_on_paid_payroll(): void
    {
        $detail = PayrollDetail::where('name', 'Transport')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('payroll-details.update', $detail), [
            'name' => 'Transport',
            'amount' => 600_000,
        ]);

        $this->payroll->update(['status' => 'paid', 'paid_at' => now()]);

        $response = $this->actingAs($this->admin)->post(route('payrolls.undo', $this->payroll));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(1, PayrollDetailChange::whereNull('undone_at')->whereNull('superseded_at')->count());
    }
}
