<?php

use App\Http\Controllers\Api\AttendanceApiController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'attendance.api',
    'throttle:60,1',
])->group(function () {
    Route::get('/attendance/today', [AttendanceApiController::class, 'today']);
    Route::get('/attendance', [AttendanceApiController::class, 'index']);
    Route::get('/attendance/employee/{identifier}', [AttendanceApiController::class, 'employee'])
        ->where('identifier', '[^/]+');
});
