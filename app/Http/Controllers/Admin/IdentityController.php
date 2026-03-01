<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncIdentityData;
use App\Models\ActivityLog;
use App\Models\IdentityGroup;
use App\Models\IdentityLicense;
use App\Models\IdentitySyncLog;
use App\Models\IdentityUser;
use App\Models\Setting;
use App\Services\Identity\GraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdentityController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // Users
    // ─────────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $query = IdentityUser::orderBy('display_name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('display_name',        'like', "%{$s}%")
                  ->orWhere('user_principal_name','like', "%{$s}%")
                  ->orWhere('mail',              'like', "%{$s}%")
                  ->orWhere('department',        'like', "%{$s}%");
            });
        }
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('status')) {
            $query->where('account_enabled', $request->status === 'enabled');
        }

        $users       = $query->paginate(50)->withQueryString();
        $departments = IdentityUser::whereNotNull('department')
                        ->distinct()->orderBy('department')->pluck('department');
        $lastSync    = IdentitySyncLog::where('status', 'completed')->latest()->first();

        return view('admin.identity.users', compact('users', 'departments', 'lastSync'));
    }

    public function userDetail(string $azureId)
    {
        $user     = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $licenses = IdentityLicense::whereIn('sku_id', $user->assigned_licenses ?? [])->get();
        $allLicenses = IdentityLicense::orderBy('display_name')->get();
        $groups   = IdentityGroup::whereIn('azure_id', $user->member_of ?? [])->get();
        $allGroups = IdentityGroup::orderBy('display_name')->get();

        return view('admin.identity.user-detail', compact('user', 'licenses', 'allLicenses', 'groups', 'allGroups'));
    }

    // ─────────────────────────────────────────────────────────────
    // Licenses
    // ─────────────────────────────────────────────────────────────

    public function licenses()
    {
        $licenses = IdentityLicense::orderBy('display_name')->get();
        $lastSync = IdentitySyncLog::where('status', 'completed')->latest()->first();
        return view('admin.identity.licenses', compact('licenses', 'lastSync'));
    }

    // ─────────────────────────────────────────────────────────────
    // Groups
    // ─────────────────────────────────────────────────────────────

    public function groups(Request $request)
    {
        $query = IdentityGroup::orderBy('display_name');

        if ($request->filled('search')) {
            $query->where('display_name', 'like', '%' . $request->search . '%');
        }

        $groups   = $query->paginate(50)->withQueryString();
        $lastSync = IdentitySyncLog::where('status', 'completed')->latest()->first();
        return view('admin.identity.groups', compact('groups', 'lastSync'));
    }

    // ─────────────────────────────────────────────────────────────
    // Sync Logs
    // ─────────────────────────────────────────────────────────────

    public function syncLogs()
    {
        $logs = IdentitySyncLog::orderByDesc('created_at')->paginate(30);
        return view('admin.identity.sync-logs', compact('logs'));
    }

    // ─────────────────────────────────────────────────────────────
    // Sync trigger
    // ─────────────────────────────────────────────────────────────

    public function sync()
    {
        SyncIdentityData::dispatch();

        ActivityLog::create([
            'model_type' => 'Identity',
            'model_id'   => 0,
            'action'     => 'synced',
            'changes'    => ['type' => 'identity_sync_dispatched'],
            'user_id'    => Auth::id(),
        ]);

        return back()->with('success', 'Identity sync job dispatched. Data will update shortly.');
    }

    // ─────────────────────────────────────────────────────────────
    // Test Graph Connection (AJAX)
    // ─────────────────────────────────────────────────────────────

    public function testConnection(Request $request)
    {
        $request->validate([
            'tenant_id'     => 'required|string',
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
        ]);

        try {
            $graph   = new GraphService($request->tenant_id, $request->client_id, $request->client_secret);
            $orgName = $graph->testConnection();

            ActivityLog::create([
                'model_type' => 'Identity',
                'model_id'   => 0,
                'action'     => 'test_connection',
                'changes'    => ['result' => 'success', 'org' => $orgName],
                'user_id'    => Auth::id(),
            ]);

            return response()->json(['success' => true, 'message' => "Connected to: {$orgName}"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // User Actions (manage-identity)
    // ─────────────────────────────────────────────────────────────

    public function toggleUser(Request $request, string $azureId)
    {
        $user  = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $graph = new GraphService();

        if ($user->account_enabled) {
            $graph->disableUser($azureId);
            $user->update(['account_enabled' => false]);
            $action = 'disabled';
        } else {
            $graph->enableUser($azureId);
            $user->update(['account_enabled' => true]);
            $action = 'enabled';
        }

        ActivityLog::create([
            'model_type' => 'IdentityUser',
            'model_id'   => $user->id,
            'action'     => $action,
            'changes'    => ['user' => $user->user_principal_name],
            'user_id'    => Auth::id(),
        ]);

        return back()->with('success', "User {$user->display_name} has been {$action}.");
    }

    public function resetPassword(Request $request, string $azureId)
    {
        $request->validate([
            'new_password'   => 'required|string|min:8',
            'force_change'   => 'boolean',
        ]);

        $user  = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $graph = new GraphService();
        $graph->resetPassword($azureId, $request->new_password, (bool) $request->force_change);

        ActivityLog::create([
            'model_type' => 'IdentityUser',
            'model_id'   => $user->id,
            'action'     => 'password_reset',
            'changes'    => ['user' => $user->user_principal_name],
            'user_id'    => Auth::id(),
        ]);

        return back()->with('success', "Password reset for {$user->display_name}.");
    }

    public function assignLicense(Request $request, string $azureId)
    {
        $request->validate(['sku_id' => 'required|string']);

        $user  = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $graph = new GraphService();
        $graph->assignLicense($azureId, $request->sku_id);

        $licenses   = array_unique(array_merge($user->assigned_licenses ?? [], [$request->sku_id]));
        $user->update(['assigned_licenses' => $licenses, 'licenses_count' => count($licenses)]);

        return back()->with('success', 'License assigned.');
    }

    public function removeLicense(Request $request, string $azureId)
    {
        $request->validate(['sku_id' => 'required|string']);

        $user  = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $graph = new GraphService();
        $graph->removeLicense($azureId, $request->sku_id);

        $licenses   = array_values(array_filter($user->assigned_licenses ?? [], fn($s) => $s !== $request->sku_id));
        $user->update(['assigned_licenses' => $licenses, 'licenses_count' => count($licenses)]);

        return back()->with('success', 'License removed.');
    }

    public function addGroup(Request $request, string $azureId)
    {
        $request->validate(['group_id' => 'required|string']);

        $user  = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $graph = new GraphService();
        $graph->addUserToGroup($azureId, $request->group_id);

        $groups = array_unique(array_merge($user->member_of ?? [], [$request->group_id]));
        $user->update(['member_of' => $groups, 'groups_count' => count($groups)]);

        return back()->with('success', 'User added to group.');
    }

    public function removeGroup(Request $request, string $azureId)
    {
        $request->validate(['group_id' => 'required|string']);

        $user  = IdentityUser::where('azure_id', $azureId)->firstOrFail();
        $graph = new GraphService();
        $graph->removeUserFromGroup($azureId, $request->group_id);

        $groups = array_values(array_filter($user->member_of ?? [], fn($g) => $g !== $request->group_id));
        $user->update(['member_of' => $groups, 'groups_count' => count($groups)]);

        return back()->with('success', 'User removed from group.');
    }
}
