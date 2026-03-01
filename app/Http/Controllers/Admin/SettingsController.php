<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\NetworkFloor;
use App\Models\NetworkOffice;
use App\Models\NetworkRack;
use App\Models\Setting;
use App\Models\UcmServer;
use App\Services\Network\MerakiService;
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

    // ─────────────────────────────────────────────────────────────
    // Meraki Network Settings
    // ─────────────────────────────────────────────────────────────

    /**
     * Update Meraki API configuration
     */
    public function updateMeraki(Request $request)
    {
        $request->validate([
            'meraki_org_id'            => 'nullable|string|max:100',
            'meraki_api_key'           => 'nullable|string|max:500',
            'meraki_polling_interval'  => 'required|integer|min:5|max:1440',
        ]);

        $settings = Setting::get();
        $settings->meraki_enabled          = $request->boolean('meraki_enabled');
        $settings->meraki_org_id           = $request->meraki_org_id;
        $settings->meraki_polling_interval = (int) $request->meraki_polling_interval;

        if ($request->filled('meraki_api_key')) {
            $settings->meraki_api_key = $request->meraki_api_key;
        }

        $settings->save();

        ActivityLog::create([
            'model_type' => 'Setting',
            'model_id'   => 1,
            'action'     => 'updated',
            'changes'    => [
                'meraki_enabled'          => $settings->meraki_enabled,
                'meraki_org_id'           => $request->meraki_org_id,
                'meraki_polling_interval' => $settings->meraki_polling_interval,
                'api_key_changed'         => $request->filled('meraki_api_key'),
            ],
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Meraki settings updated.');
    }

    /**
     * Update Microsoft Graph / Identity settings
     */
    public function updateGraph(Request $request)
    {
        $request->validate([
            'graph_tenant_id'          => 'nullable|string|max:100',
            'graph_client_id'          => 'nullable|string|max:100',
            'graph_client_secret'      => 'nullable|string|max:500',
            'graph_default_password'   => 'nullable|string|max:255',
            'graph_default_license_sku'=> 'nullable|string|max:100',
            'identity_sync_interval'   => 'required|integer|min:5|max:1440',
        ]);

        $settings = Setting::get();
        $settings->identity_sync_enabled    = $request->boolean('identity_sync_enabled');
        $settings->graph_tenant_id          = $request->graph_tenant_id;
        $settings->graph_client_id          = $request->graph_client_id;
        $settings->graph_default_password   = $request->graph_default_password;
        $settings->graph_default_license_sku= $request->graph_default_license_sku;
        $settings->identity_sync_interval   = (int) $request->identity_sync_interval;

        if ($request->filled('graph_client_secret')) {
            $settings->graph_client_secret = $request->graph_client_secret;
        }

        $settings->save();

        ActivityLog::create([
            'model_type' => 'Setting',
            'model_id'   => 1,
            'action'     => 'updated',
            'changes'    => [
                'identity_sync_enabled' => $settings->identity_sync_enabled,
                'graph_tenant_id'       => $request->graph_tenant_id,
                'secret_changed'        => $request->filled('graph_client_secret'),
            ],
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Identity (Graph) settings updated.');
    }

    // ─────────────────────────────────────────────────────────────
    // Locations (Settings → Locations)
    // ─────────────────────────────────────────────────────────────

    public function locations()
    {
        $branches = Branch::with([
            'networkFloors.racks',
            'networkFloors.offices',
        ])->orderBy('name')->get();

        return view('admin.settings.locations', compact('branches'));
    }

    // ─────────────────────────────────────────────────────────────
    // Departments (Settings → Departments)
    // ─────────────────────────────────────────────────────────────

    public function departments()
    {
        $departments = Department::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.settings.departments', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments,name',
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $dept = Department::create($data);

        if ($request->expectsJson()) {
            return response()->json(['id' => $dept->id, 'name' => $dept->name], 201);
        }

        return back()->with('success', "Department \"{$data['name']}\" created.");
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments,name,' . $department->id,
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $department->update($data);
        return back()->with('success', "Department \"{$department->name}\" updated.");
    }

    public function destroyDepartment(Department $department)
    {
        $name = $department->name;
        $department->delete();
        return back()->with('success', "Department \"{$name}\" deleted.");
    }
}
