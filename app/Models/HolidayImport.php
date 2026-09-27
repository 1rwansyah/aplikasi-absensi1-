<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayImport extends Model
{
    protected $fillable = [
        'year',
        'type',
        'source',
        'synced_at',
        'response_json',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
