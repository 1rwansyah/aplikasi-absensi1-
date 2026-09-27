<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetailChange extends Model
{
    protected $fillable = [
        'payroll_id',
        'payroll_detail_id',
        'user_id',
        'action',
        'before',
        'after',
        'summary',
        'undone_at',
        'undone_by',
        'superseded_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'undone_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function payrollDetail(): BelongsTo
    {
        return $this->belongsTo(PayrollDetail::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }

    public function scopeUndoable(Builder $query): Builder
    {
        return $query->whereNull('undone_at')->whereNull('superseded_at');
    }

    public function isUndoable(): bool
    {
        return $this->undone_at === null && $this->superseded_at === null;
    }
}
