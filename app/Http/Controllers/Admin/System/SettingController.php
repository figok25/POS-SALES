<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\SettingRequest;
use App\Models\AppSetting;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;

/**
 * System - Settings (Blueprint #47, permission 'system.manage').
 * Singleton config -- lihat App\Models\AppSetting.
 */
class SettingController extends Controller
{
    public function edit()
    {
        $setting = AppSetting::current();

        return view('admin.system.settings.edit', compact('setting'));
    }

    public function update(SettingRequest $request)
    {
        $setting = AppSetting::current();
        $before = $setting->toArray();

        $data = $request->safe()->only('app_name');

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = null;
        }

        $setting->update($data);

        AuditLogger::log('update', 'System', AppSetting::class, $setting->id, $before, $setting->fresh()->toArray());

        return redirect()->route('admin.system.settings.edit')->with('status', 'Pengaturan berhasil disimpan.');
    }
}
