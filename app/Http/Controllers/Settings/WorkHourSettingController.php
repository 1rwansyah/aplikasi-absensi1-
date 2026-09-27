<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WorkSchedule;
use App\Services\ActivityLogService;
use App\Support\TimeFormat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkHourSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.work-hours', [
            'settings' => Setting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_start' => ['required', 'date_format:H:i'],
            'late_limit' => ['required', 'date_format:H:i', 'after:office_start'],
            'clock_out_start' => ['required', 'date_format:H:i'],
            'clock_out_limit' => ['required', 'date_format:H:i', 'different:clock_out_start'],
            'security_clock_in_start' => ['required', 'date_format:H:i'],
            'security_late_limit' => ['required', 'date_format:H:i', 'after:security_clock_in_start'],
            'security_clock_out_start' => ['required', 'date_format:H:i'],
            'security_clock_out_limit' => ['nullable', 'date_format:H:i', 'different:security_clock_out_start'],
            'ob_clock_in_start' => ['required', 'date_format:H:i'],
            'ob_late_limit' => ['required', 'date_format:H:i', 'after:ob_clock_in_start'],
            'ob_clock_out_start' => ['required', 'date_format:H:i'],
            'ob_clock_out_limit' => ['nullable', 'date_format:H:i', 'different:ob_clock_out_start'],
            'engineering_clock_in_start' => ['required', 'date_format:H:i'],
            'engineering_late_limit' => ['required', 'date_format:H:i', 'after:engineering_clock_in_start'],
            'engineering_clock_out_start' => ['required', 'date_format:H:i', 'after:engineering_late_limit'],
            'engineering_clock_out_limit' => ['nullable', 'date_format:H:i', 'different:engineering_clock_out_start'],
        ], [
            'late_limit.after' => 'Batas telat harus setelah jam masuk.',
            'clock_out_limit.different' => 'Batas pulang lewat tidak boleh sama dengan jam pulang.',
            'security_late_limit.after' => 'Batas telat security harus setelah jam masuk security.',
            'security_clock_out_limit.different' => 'Batas pulang lewat security tidak boleh sama dengan jam pulang security.',
            'ob_late_limit.after' => 'Batas telat OB harus setelah jam masuk OB.',
            'ob_clock_out_limit.different' => 'Batas pulang lewat OB tidak boleh sama dengan jam pulang OB.',
            'engineering_late_limit.after' => 'Batas telat Staff Engineering harus setelah jam masuk.',
            'engineering_clock_out_start.after' => 'Jam pulang Staff Engineering harus setelah batas telat.',
            'engineering_clock_out_limit.different' => 'Batas pulang lewat Staff Engineering tidak boleh sama dengan jam pulang.',
        ]);

        $setting = Setting::current();
        $setting->update([
            'office_start' => TimeFormat::storage($validated['office_start']),
            'late_limit' => TimeFormat::storage($validated['late_limit']),
            'clock_out_start' => TimeFormat::storage($validated['clock_out_start']),
            'clock_out_limit' => TimeFormat::storage($validated['clock_out_limit']),
        ]);

        // Update regular work schedule
        WorkSchedule::query()
            ->where('code', 'regular')
            ->update([
                'clock_in_start' => TimeFormat::storage($validated['office_start']),
                'late_limit' => TimeFormat::storage($validated['late_limit']),
                'clock_out_start' => TimeFormat::storage($validated['clock_out_start']),
                'clock_out_limit' => TimeFormat::storage($validated['clock_out_limit']),
            ]);

        // Update security work schedule
        WorkSchedule::query()
            ->where('code', 'security')
            ->update([
                'clock_in_start' => TimeFormat::storage($validated['security_clock_in_start']),
                'late_limit' => TimeFormat::storage($validated['security_late_limit']),
                'clock_out_start' => TimeFormat::storage($validated['security_clock_out_start']),
                'clock_out_limit' => $validated['security_clock_out_limit'] ? TimeFormat::storage($validated['security_clock_out_limit']) : null,
            ]);

        // Update OB work schedule
        WorkSchedule::query()
            ->where('code', 'ob')
            ->update([
                'clock_in_start' => TimeFormat::storage($validated['ob_clock_in_start']),
                'late_limit' => TimeFormat::storage($validated['ob_late_limit']),
                'clock_out_start' => TimeFormat::storage($validated['ob_clock_out_start']),
                'clock_out_limit' => $validated['ob_clock_out_limit'] ? TimeFormat::storage($validated['ob_clock_out_limit']) : null,
            ]);

        WorkSchedule::query()
            ->where('code', 'engineering')
            ->update([
                'clock_in_start' => TimeFormat::storage($validated['engineering_clock_in_start']),
                'late_limit' => TimeFormat::storage($validated['engineering_late_limit']),
                'clock_out_start' => TimeFormat::storage($validated['engineering_clock_out_start']),
                'clock_out_limit' => $validated['engineering_clock_out_limit']
                    ? TimeFormat::storage($validated['engineering_clock_out_limit'])
                    : null,
            ]);

        ActivityLogService::log(
            auth()->user(),
            'update',
            'Memperbarui pengaturan jam kerja',
            $setting
        );

        return back()->with('success', 'Pengaturan jam kerja berhasil disimpan.');
    }
}
