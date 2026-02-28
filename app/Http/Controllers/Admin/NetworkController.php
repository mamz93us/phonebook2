<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncMerakiData;
use App\Models\ActivityLog;
use App\Models\NetworkClient;
use App\Models\NetworkEvent;
use App\Models\NetworkSwitch;
use App\Models\Setting;
use App\Services\Network\MerakiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // Overview
    // ─────────────────────────────────────────────────────────────

    public function overview()
    {
        $totalSwitches    = NetworkSwitch::count();
        $onlineSwitches   = NetworkSwitch::where('status', 'online')->count();
        $offlineSwitches  = NetworkSwitch::where('status', 'offline')->count();
        $alertingSwitches = NetworkSwitch::where('status', 'alerting')->count();
        $totalClients     = NetworkClient::count();
        $onlineClients    = NetworkClient::where('status', 'Online')->count();
        $totalPorts       = \App\Models\NetworkPort::count();
        $connectedPorts   = \App\Models\NetworkPort::where('status', 'Connected')->count();

        $switches = NetworkSwitch::orderByRaw("
            CASE status
                WHEN 'online'   THEN 1
                WHEN 'alerting' THEN 2
                WHEN 'offline'  THEN 3
                ELSE 4
            END
        ")->orderBy('name')->get();

        $lastSync = NetworkSwitch::max('updated_at');

        $settings = Setting::get();

        return view('admin.network.overview', compact(
            'totalSwitches', 'onlineSwitches', 'offlineSwitches', 'alertingSwitches',
            'totalClients', 'onlineClients', 'totalPorts', 'connectedPorts',
            'switches', 'lastSync', 'settings'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Switch list
    // ─────────────────────────────────────────────────────────────

    public function switches(Request $request)
    {
        $query = NetworkSwitch::orderByRaw("
            CASE status
                WHEN 'online'   THEN 1
                WHEN 'alerting' THEN 2
                WHEN 'offline'  THEN 3
                ELSE 4
            END
        ")->orderBy('name');

        if ($request->filled('network')) {
            $query->where('network_id', $request->network);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $switches  = $query->get();
        $networks  = NetworkSwitch::select('network_id', 'network_name')
                        ->distinct()->orderBy('network_name')->get();
        $lastSync  = NetworkSwitch::max('updated_at');

        return view('admin.network.switches', compact('switches', 'networks', 'lastSync'));
    }

    // ─────────────────────────────────────────────────────────────
    // Switch detail (ports + clients)
    // ─────────────────────────────────────────────────────────────

    public function switchDetail(string $serial)
    {
        $switch  = NetworkSwitch::where('serial', $serial)->firstOrFail();
        $ports   = $switch->ports()
                        ->orderByRaw("CAST(port_id AS UNSIGNED) ASC, port_id ASC")
                        ->get();
        $clients = $switch->clients()
                        ->orderBy('status')->orderBy('hostname')
                        ->get();

        return view('admin.network.switch-detail', compact('switch', 'ports', 'clients'));
    }

    // ─────────────────────────────────────────────────────────────
    // Clients
    // ─────────────────────────────────────────────────────────────

    public function clients(Request $request)
    {
        $query = NetworkClient::with('networkSwitch')
                    ->orderByRaw("CASE status WHEN 'Online' THEN 1 ELSE 2 END")
                    ->orderBy('hostname');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('hostname',     'like', "%{$s}%")
                  ->orWhere('ip',         'like', "%{$s}%")
                  ->orWhere('mac',        'like', "%{$s}%")
                  ->orWhere('manufacturer','like', "%{$s}%")
                  ->orWhere('description','like', "%{$s}%");
            });
        }

        if ($request->filled('vlan')) {
            $query->where('vlan', (int) $request->vlan);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $clients  = $query->paginate(50)->withQueryString();
        $vlans    = NetworkClient::whereNotNull('vlan')
                        ->distinct()->orderBy('vlan')->pluck('vlan');

        return view('admin.network.clients', compact('clients', 'vlans'));
    }

    // ─────────────────────────────────────────────────────────────
    // Events / Change monitor
    // ─────────────────────────────────────────────────────────────

    public function events(Request $request)
    {
        $query = NetworkEvent::orderByDesc('occurred_at');

        if ($request->filled('serial')) {
            $query->where('switch_serial', $request->serial);
        }
        if ($request->filled('type')) {
            $query->where('event_type', $request->type);
        }
        if ($request->filled('network')) {
            $query->where('network_id', $request->network);
        }

        $events     = $query->paginate(50)->withQueryString();
        $switches   = NetworkSwitch::orderBy('name')->get(['serial', 'name']);
        $eventTypes = NetworkEvent::selectRaw('event_type')
                        ->distinct()->orderBy('event_type')->pluck('event_type');
        $networks   = NetworkSwitch::select('network_id', 'network_name')
                        ->distinct()->orderBy('network_name')->get();

        return view('admin.network.events', compact('events', 'switches', 'eventTypes', 'networks'));
    }

    // ─────────────────────────────────────────────────────────────
    // Sync trigger (dispatches queue job)
    // ─────────────────────────────────────────────────────────────

    public function sync()
    {
        try {
            SyncMerakiData::dispatch();

            ActivityLog::create([
                'model_type' => 'Network',
                'model_id'   => 0,
                'action'     => 'synced',
                'changes'    => ['type' => 'meraki_sync_dispatched'],
                'user_id'    => Auth::id(),
            ]);

            return back()->with('success', 'Meraki sync job dispatched. Data will update shortly.');
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Test connection (AJAX – called from Settings page)
    // ─────────────────────────────────────────────────────────────

    public function testConnection(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'org_id'  => 'required|string',
        ]);

        try {
            $meraki  = new MerakiService($request->api_key, $request->org_id);
            $orgName = $meraki->testConnection();

            return response()->json([
                'success' => true,
                'message' => "Connected to organisation: {$orgName}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
