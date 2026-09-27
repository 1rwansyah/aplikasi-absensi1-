<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_id',
        'name',
        'type',
        'amount',
        'notes',
        'is_adjustment',
    ];

    protected $casts = [
        'is_adjustment' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->type, ['allowance', 'deduction'], true);
    }

    public function isDeletable(): bool
    {
        return $this->isEditable();
    }
}
