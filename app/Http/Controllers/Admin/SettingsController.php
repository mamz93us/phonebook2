<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Show settings form
     */
    public function index()
    {
        $settings = Setting::get();
        return view('admin.settings', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $settings = Setting::get();
        $settings->company_name = $request->company_name;

        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            // Delete old logo
            if ($settings->company_logo && Storage::disk('public')->exists($settings->company_logo)) {
                Storage::disk('public')->delete($settings->company_logo);
            }

            // Store new logo
            $path = $request->file('company_logo')->store('logos', 'public');
            $settings->company_logo = $path;
        }

        $settings->save();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Delete logo
     */
    public function deleteLogo()
    {
        $settings = Setting::get();

        if ($settings->company_logo && Storage::disk('public')->exists($settings->company_logo)) {
            Storage::disk('public')->delete($settings->company_logo);
        }

        $settings->company_logo = null;
        $settings->save();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Logo deleted successfully.');
    }
}
