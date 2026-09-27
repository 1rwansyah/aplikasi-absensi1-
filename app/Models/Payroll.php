<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'period_month',
        'period_year',
        'work_days',
        'present_days',
        'absent_days',
        'sick_days',
        'leave_days',
        'late_days',
        'basic_salary',
        'prorate_salary',
        'full_monthly_salary',
        'total_allowance',
        'total_deduction',
        'gross_salary',
        'net_salary',
        'rounding_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function detailChanges(): HasMany
    {
        return $this->hasMany(PayrollDetailChange::class);
    }

    public function systemSnapshot(): HasOne
    {
        return $this->hasOne(PayrollSystemSnapshot::class);
    }

    public function formattedWorkDays(): string
    {
        $val = (float) $this->work_days;

        return $val == floor($val) ? number_format($val, 0) : rtrim(rtrim(number_format($val, 1), '0'), '.');
    }

    public function roundedNetSalary(): float
    {
        return $this->net_salary + $this->rounding_amount;
    }

    public function roundingAmount(): float
    {
        return $this->rounding_amount;
    }
}
