<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity.
     *
     * @param  User|null  $user
     * @return ActivityLog|null
     */
    public static function log($user, string $action, string $description, ?Model $subject = null)
    {
        if (! $user) {
            return null;
        }

        // Determine primary role for logging context
        $role = 'user';
        if ($user->hasRole('admin')) {
            $role = 'admin';
        } elseif ($user->hasRole('hr')) {
            $role = 'hr';
        }

        try {
            return ActivityLog::create([
                'user_id' => $user->id,
                'role' => $role,
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject ? $subject->getKey() : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Exception $e) {
            // Safe fallback: do not crash the app if logging fails
            Log::error('ActivityLogService failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function logDataChange(
        User $user,
        string $action,
        string $summary,
        Model $subject,
        array $before,
        array $after,
        string $reason,
    ): ?ActivityLog {
        $description = $summary."\n".json_encode([
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
        ], JSON_UNESCAPED_UNICODE);

        return self::log($user, $action, $description, $subject);
    }
}
