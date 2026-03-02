<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use App\Models\IdentityUser;
use App\Models\NetworkSwitch;
use App\Models\NocEvent;
use App\Models\Printer;
use App\Services\HealthScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NocController extends Controller
{
    public function __construct(private HealthScoringService $health) {}

    public function dashboard()
    {
        // Global Identity summary
        $totalUsers       = IdentityUser::count();
        $licensedUsers    = IdentityUser::where(fn ($q) => $q->whereNotNull('assigned_licenses')->where('assigned_licenses', '!=', '[]')->where('assigned_licenses', '!=', 'null'))->count();
        $disabledUsers    = IdentityUser::where('account_enabled', false)->count();
        $licensedPercent  = $totalUsers > 0 ? (int) round($licensedUsers / $totalUsers * 100) : 0;

        // Global Network summary
        $totalSwitches  = NetworkSwitch::count();
        $onlineSwitches = NetworkSwitch::where('status', 'online')->count();
        $onlinePercent  = $totalSwitches > 0 ? (int) round($onlineSwitches / $totalSwitches * 100) : 0;

        // Global Assets summary
        $totalDevices     = Device::count();
        $assignedDevices  = Device::where('status', 'assigned')->count();
        $missingCreds     = Device::whereDoesntHave('credentials')->count();

        // Printers overdue service
        $printersOverdue = Printer::all()->filter(fn ($p) => $p->isMaintenanceDue())->count();

        // Branch health scores
        $branches = $this->health->allBranches();

        // Open NOC events
        $openEvents = NocEvent::open()->orderByDesc('severity')->orderByDesc('last_seen')->limit(10)->get();

        return view('admin.noc.dashboard', compact(
            'totalUsers', 'licensedUsers', 'disabledUsers', 'licensedPercent',
            'totalSwitches', 'onlineSwitches', 'onlinePercent',
            'totalDevices', 'assignedDevices', 'missingCreds', 'printersOverdue',
            'branches', 'openEvents'
        ));
    }

    public function branch(Branch $branch)
    {
        $score   = $this->health->scoreForBranch($branch->id);
        $switches = NetworkSwitch::where('branch_id', $branch->id)->get();
        $devices  = Device::where('branch_id', $branch->id)->with('credentials')->get();
        $printers = Printer::where('branch_id', $branch->id)->get();

        return view('admin.noc.branch', compact('branch', 'score', 'switches', 'devices', 'printers'));
    }

    public function events(Request $request)
    {
        $query = NocEvent::orderByDesc('last_seen');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['open', 'acknowledged']);
        }

        $events = $query->paginate(25)->withQueryString();

        return view('admin.noc.events', compact('events'));
    }

    public function acknowledge(NocEvent $event)
    {
        $event->update([
            'status'         => 'acknowledged',
            'acknowledged_by' => Auth::id(),
        ]);

        return back()->with('success', 'Event acknowledged.');
    }

    public function resolve(NocEvent $event)
    {
        $event->update([
            'status'      => 'resolved',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Event resolved.');
    }
}
