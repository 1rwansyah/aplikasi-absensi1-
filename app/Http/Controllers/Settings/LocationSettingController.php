<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.location', [
            'settings' => Setting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_latitude' => ['required', 'numeric', 'between:-90,90'],
            'office_longitude' => ['required', 'numeric', 'between:-180,180'],
            'attendance_radius_meters' => ['required', 'integer', 'min:1', 'max:50000'],
        ]);

        $setting = Setting::current();
        $setting->update($validated);

        ActivityLogService::log(
            auth()->user(),
            'update',
            'Memperbarui pengaturan lokasi kantor',
            $setting
        );

        return back()->with('success', 'Pengaturan lokasi berhasil disimpan.');
    }
}
