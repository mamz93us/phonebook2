<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\UcmServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Show settings form
     */
    public function index()
    {
        $settings   = Setting::get();
        $ucmServers = UcmServer::orderBy('name')->get();
        return view('admin.settings', compact('settings', 'ucmServers'));
    }

    /**
     * Update general settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $settings = Setting::get();
        $old = ['company_name' => $settings->company_name];

        $settings->company_name = $request->company_name;

        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            if ($settings->company_logo && Storage::disk('public')->exists($settings->company_logo)) {
                Storage::disk('public')->delete($settings->company_logo);
            }
            $path = $request->file('company_logo')->store('logos', 'public');
            $settings->company_logo = $path;
        }

        $settings->save();

        ActivityLog::create([
            'model_type' => 'Setting',
            'model_id'   => 1,
            'action'     => 'updated',
            'changes'    => [
                'old' => $old,
                'new' => ['company_name' => $settings->company_name, 'logo_changed' => $request->hasFile('company_logo')],
            ],
            'user_id' => Auth::id(),
        ]);

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

        ActivityLog::create([
            'model_type' => 'Setting',
            'model_id'   => 1,
            'action'     => 'deleted',
            'changes'    => ['company_logo' => 'removed'],
            'user_id'    => Auth::id(),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Logo deleted successfully.');
    }

    /**
     * Update Microsoft SSO settings
     */
    public function updateSso(Request $request)
    {
        $request->validate([
            'sso_tenant_id'     => 'nullable|string|max:100',
            'sso_client_id'     => 'nullable|string|max:100',
            'sso_client_secret' => 'nullable|string|max:500',
            'sso_default_role'  => 'required|in:super_admin,admin,viewer',
        ]);

        $settings = Setting::get();
        $settings->sso_enabled      = $request->boolean('sso_enabled');
        $settings->sso_tenant_id    = $request->sso_tenant_id;
        $settings->sso_client_id    = $request->sso_client_id;
        $settings->sso_default_role = $request->sso_default_role;

        if ($request->filled('sso_client_secret')) {
            $settings->sso_client_secret = $request->sso_client_secret;
        }

        $settings->save();

        ActivityLog::create([
            'model_type' => 'Setting',
            'model_id'   => 1,
            'action'     => 'updated',
            'changes'    => [
                'sso_enabled'      => $settings->sso_enabled,
                'sso_tenant_id'    => $request->sso_tenant_id,
                'sso_client_id'    => $request->sso_client_id,
                'sso_default_role' => $request->sso_default_role,
                'secret_changed'   => $request->filled('sso_client_secret'),
            ],
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'SSO settings updated.');
    }
}
