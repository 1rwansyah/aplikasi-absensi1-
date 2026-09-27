<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Policies\AttendancePolicy;
use App\Services\AbsenceThresholdMonitoringService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Gate::policy(Attendance::class, AttendancePolicy::class);

        App::setLocale('id');
        Carbon::setLocale('id');

        View::composer('layouts.sidebar', function ($view) {
            $user = auth()->user();
            $pendingCount = 0;
            $absenceThresholdCount = 0;

            if ($user?->isAdmin()) {
                $pendingCount = Attendance::query()->pendingLeave()->count();
                $absenceThresholdCount = app(AbsenceThresholdMonitoringService::class)
                    ->thresholdCountForMonth();
            }

            $view->with([
                'pendingLeaveVerificationCount' => $pendingCount,
                'absenceThresholdCount' => $absenceThresholdCount,
            ]);
        });
    }
}
