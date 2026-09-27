<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSystemSnapshot extends Model
{
    protected $fillable = [
        'payroll_id',
        'snapshot',
        'generated_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'generated_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
