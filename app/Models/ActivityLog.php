<?php

namespace App\Models;

use App\Support\ActivityLogChangeFormatter;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function summary(): string
    {
        return explode("\n", $this->description, 2)[0];
    }

    public function inlineChangeDetail(): ?string
    {
        $parts = explode("\n", $this->description, 2);

        if (! isset($parts[1])) {
            return null;
        }

        $payload = json_decode($parts[1], true);

        if (! is_array($payload)) {
            return null;
        }

        $formatted = app(ActivityLogChangeFormatter::class)->format($payload);

        return $formatted !== '' ? $formatted : null;
    }
}
