<?php

namespace App\Support;

class NavigationContext
{
    /** @return list<string> */
    public static function adminRouteNames(): array
    {
        return [
            'dashboard',
            'employees.*',
            'admin.attendance.*',
            'admin.absence-threshold.*',
            'leave-verification.*',
            'payrolls.*',
            'settings.*',
            'work-calendars.*',
            'activity-log.*',
        ];
    }

    public static function onAdminRoute(): bool
    {
        return request()->routeIs(self::adminRouteNames());
    }
}
