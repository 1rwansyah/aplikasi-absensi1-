<?php

use App\Http\Controllers\AbsenceThresholdController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorNoteController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FaceVerificationController;
use App\Http\Controllers\LeaveVerificationController;
use App\Http\Controllers\MyPayrollController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\LocationSettingController;
use App\Http\Controllers\Settings\SecurityScheduleController;
use App\Http\Controllers\Settings\WorkHourSettingController;
use App\Http\Controllers\WorkCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:employee,hr,admin'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');
    Route::get('/leave-verification/my-submissions', [LeaveVerificationController::class, 'mySubmissions'])
        ->name('leave-verification.my-submissions');
    Route::get('/attendance/server-time', [AttendanceController::class, 'serverTime'])->name('attendance.server-time');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/leave', [AttendanceController::class, 'submitLeave'])->name('attendance.leave');
    Route::post('/attendance/face/verify', [FaceVerificationController::class, 'verify'])->name('attendance.face.verify');
    Route::post('/attendance/face/sync-descriptor', [FaceVerificationController::class, 'syncDescriptor'])->name('attendance.face.sync-descriptor');
    Route::get('/my-payrolls', [MyPayrollController::class, 'index'])->name('my-payrolls.index');
    Route::get('/my-payrolls/{payroll}', [MyPayrollController::class, 'show'])->name('my-payrolls.show');
    Route::get('/my-payrolls/{payroll}/pdf', [MyPayrollController::class, 'downloadPdf'])->name('my-payrolls.pdf');
});

Route::middleware(['auth', 'verified', 'role:admin,hr'])->group(function () {
    Route::resource('/employees', EmployeeController::class);
    Route::get('/admin/attendance', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/admin/alfa-izin', [AbsenceThresholdController::class, 'index'])
        ->name('admin.absence-threshold.index');
    Route::get('/admin/alfa-izin/export/excel', [AbsenceThresholdController::class, 'exportExcel'])
        ->name('admin.absence-threshold.export.excel');
    Route::get('/admin/attendance/schedule-preview', [AdminAttendanceController::class, 'schedulePreview'])
        ->name('admin.attendance.schedule-preview');
    Route::get('/admin/attendance/create', [AdminAttendanceController::class, 'create'])->name('admin.attendance.create');
    Route::post('/admin/attendance', [AdminAttendanceController::class, 'store'])->name('admin.attendance.store');
    Route::get('/admin/attendance/{attendance}/edit', [AdminAttendanceController::class, 'edit'])->name('admin.attendance.edit');
    Route::patch('/admin/attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/admin/attendance/{attendance}/verification-photo/{moment}', [AdminAttendanceController::class, 'verificationPhoto'])->name('admin.attendance.verification-photo');
    Route::get('/leave-verification', [LeaveVerificationController::class, 'index'])->name('leave-verification.index');
    Route::patch('/leave-verification/{attendance}/verify', [LeaveVerificationController::class, 'verify'])->name('leave-verification.verify');
    Route::get('/work-calendars', [WorkCalendarController::class, 'index'])->name('work-calendars.index');
    Route::post('/work-calendars/generate', [WorkCalendarController::class, 'generate'])->name('work-calendars.generate');
    Route::post('/work-calendars/sync-holidays', [WorkCalendarController::class, 'syncHolidays'])->name('work-calendars.sync-holidays');
    Route::patch('/work-calendars/{workCalendar}', [WorkCalendarController::class, 'update'])->name('work-calendars.update');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::post('/attendances/send-whatsapp-report', [AdminAttendanceController::class, 'sendWhatsappReport'])
        ->name('attendances.send-whatsapp-report');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::redirect('/shifts', '/settings/work-hours')->name('shifts.edit');
        Route::get('/work-hours', [WorkHourSettingController::class, 'edit'])->name('work-hours.edit');
        Route::patch('/work-hours', [WorkHourSettingController::class, 'update'])->name('work-hours.update');

        Route::get('/location', [LocationSettingController::class, 'edit'])->name('location.edit');
        Route::patch('/location', [LocationSettingController::class, 'update'])->name('location.update');

        Route::get('/security-schedules', [SecurityScheduleController::class, 'index'])
            ->name('security-schedules.index');

        Route::post('/security-schedules', [SecurityScheduleController::class, 'store'])
            ->name('security-schedules.store');

        Route::post('/security-schedules/import', [SecurityScheduleController::class, 'import'])
            ->name('security-schedules.import');
    });

    Route::get('/settings/attendance', fn () => redirect()->route('settings.work-hours.edit'))->name('settings.attendance.edit');
    Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
    Route::get('/payrolls/export', [PayrollController::class, 'export'])->name('payrolls.export');
    Route::post('/payrolls/send-wa-report', [PayrollController::class, 'sendWaReport'])->name('payrolls.send-wa-report');
    Route::get('/payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');
    Route::get('/payrolls/{payroll}/pdf', [PayrollController::class, 'downloadPdf'])->name('payrolls.pdf');
    Route::patch('/payrolls/{payroll}/mark-paid', [PayrollController::class, 'markAsPaid'])->name('payrolls.mark-paid');
    Route::post('/payrolls/{payroll}/adjustments', [PayrollController::class, 'addAdjustment'])->name('payrolls.adjustments.store');
    Route::patch('/payroll-details/{detail}', [PayrollController::class, 'updatePayrollDetail'])->name('payroll-details.update');
    Route::delete('/payroll-details/{detail}', [PayrollController::class, 'deletePayrollDetail'])->name('payroll-details.destroy');
    Route::post('/payrolls/{payroll}/undo', [PayrollController::class, 'undoLastChange'])->name('payrolls.undo');
    Route::post('/payrolls/{payroll}/reset-to-system', [PayrollController::class, 'resetToSystem'])->name('payrolls.reset-to-system');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employees/{employee}/profile-photo', [EmployeeController::class, 'profilePhoto'])
        ->name('employees.profile-photo.show');
    Route::get('/attendance/{attendance}/note', [DoctorNoteController::class, 'show'])
        ->name('attendance.note.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
