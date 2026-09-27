<?php

namespace App\Models;

use App\Enums\WorkCalendarType;
use Illuminate\Database\Eloquent\Model;

class WorkCalendar extends Model
{
    protected $fillable = [
        'date',
        'type',
        'name',
        'note',
        'is_manual_override',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => WorkCalendarType::class,
        'is_manual_override' => 'boolean',
    ];
}
