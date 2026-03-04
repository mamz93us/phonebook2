<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Mib;
use App\Models\MonitoredHost;
use App\Models\VpnTunnel;
use Illuminate\Http\Request;

class SnmpMonitoringController extends Controller
{
    public function index()
    {
        $hosts = MonitoredHost::with(['branch', 'vpnTunnel', 'mib'])->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $tunnels = VpnTunnel::orderBy('name')->get();
        $mibs = Mib::orderBy('name')->get();
        
        return view('admin.network.monitoring.index', compact('hosts', 'branches', 'tunnels', 'mibs'));
    }

    public function show(MonitoredHost $host)
    {
        $host->load([
            'branch', 
            'vpnTunnel', 
            'mib',
            'hostChecks' => fn($q) => $q->latest('checked_at')->limit(144),
            'snmpSensors.sensorMetrics' => fn($q) => $q->where('recorded_at', '>=', now()->subHours(24))->orderBy('recorded_at')
        ]);
        $mibs = Mib::orderBy('name')->get();
        return view('admin.network.monitoring.show', compact('host', 'mibs'));
    }

    public function storeHost(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'ip'             => 'required|string',
            'type'           => 'required|string',
            'branch_id'      => 'nullable|exists:branches,id',
            'vpn_id'         => 'nullable|exists:vpn_tunnels,id',
            'ping_enabled'   => 'boolean',
            'ping_interval_seconds' => 'nullable|integer|min:10',
            'ping_packet_count' => 'nullable|integer|min:1|max:20',
            'alert_email'    => 'nullable|email',
            'snmp_enabled'   => 'boolean',
            'snmp_port'      => 'nullable|integer',
            'snmp_version'   => 'required_if:snmp_enabled,1|in:v1,v2c,v3',
            'snmp_community' => 'required_if:snmp_enabled,1|string',
            'mib_id'         => 'nullable|exists:mibs,id',
        ]);

        $data = $request->except(['_token', '_method']);
        if (empty($data['ping_interval_seconds'])) {
            $data['ping_interval_seconds'] = 60;
        }
        if (empty($data['ping_packet_count'])) {
            $data['ping_packet_count'] = 3;
        }

        MonitoredHost::create($data);

        return redirect()->route('admin.network.monitoring.index')
            ->with('success', 'Monitored host added successfully.');
    }

    public function updateHost(Request $request, MonitoredHost $host)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'ip'             => 'required|string',
            'type'           => 'required|string',
            'branch_id'      => 'nullable|exists:branches,id',
            'vpn_id'         => 'nullable|exists:vpn_tunnels,id',
            'ping_enabled'   => 'boolean',
            'ping_interval_seconds' => 'nullable|integer|min:10',
            'ping_packet_count' => 'nullable|integer|min:1|max:20',
            'alert_email'    => 'nullable|email',
            'snmp_enabled'   => 'boolean',
            'snmp_port'      => 'nullable|integer',
            'snmp_version'   => 'required_if:snmp_enabled,1|in:v1,v2c,v3',
            'snmp_community' => 'nullable|string',
            'mib_id'         => 'nullable|exists:mibs,id',
        ]);

        $data = $request->except(['_token', '_method']);
        $data['ping_enabled'] = $request->boolean('ping_enabled', false);
        $data['snmp_enabled'] = $request->boolean('snmp_enabled', false);
        
        if (empty($data['ping_interval_seconds'])) {
            $data['ping_interval_seconds'] = 60;
        }

        if (empty($data['ping_packet_count'])) {
            $data['ping_packet_count'] = 3;
        }

        if (empty($data['snmp_community'])) {
            unset($data['snmp_community']);
        }

        $host->update($data);

        return redirect()->route('admin.network.monitoring.index')
            ->with('success', 'Host updated successfully.');
    }

    public function destroyHost(MonitoredHost $host)
    {
        $host->delete();
        return redirect()->route('admin.network.monitoring.index')
            ->with('success', 'Host removed from monitoring.');
    }

    public function storeSensor(Request $request, MonitoredHost $host)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'oid'           => 'required|string|max:255|regex:/^[0-9\.]+$/',
            'data_type'     => 'required|string|in:gauge,counter,rate,temperature,uptime,boolean',
            'unit'          => 'nullable|string|max:50',
            'poll_interval' => 'nullable|integer|min:10',
            'graph_enabled' => 'boolean',
        ]);

        $host->snmpSensors()->create([
            'name'          => $request->name,
            'oid'           => $request->oid,
            'data_type'     => $request->data_type,
            'unit'          => $request->unit,
            'poll_interval' => $request->poll_interval ?? 60,
            'graph_enabled' => $request->boolean('graph_enabled', false),
        ]);

        return redirect()->route('admin.network.monitoring.show', $host)
            ->with('success', 'SNMP Sensor added successfully.');
    }

    public function destroySensor(MonitoredHost $host, $sensorId)
    {
        $sensor = $host->snmpSensors()->findOrFail($sensorId);
        $sensor->delete();

        return redirect()->route('admin.network.monitoring.show', $host)
            ->with('success', 'SNMP Sensor removed.');
    }

    public function mibs()
    {
        $mibs = Mib::orderBy('name')->get();
        return view('admin.network.monitoring.mibs', compact('mibs'));
    }

    public function storeMib(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file',
        ]);

        $path = $request->file('file')->store('mibs');

        Mib::create([
            'name' => $request->name,
            'description' => $request->description,
            'file_path' => $path,
        ]);

        return redirect()->route('admin.network.monitoring.mibs')
            ->with('success', 'MIB file uploaded successfully.');
    }

    public function viewMib(Mib $mib)
    {
        $path = storage_path('app/' . $mib->file_path);
        if (!file_exists($path)) {
            $path = storage_path('app/public/' . $mib->file_path);
        }

        if (!file_exists($path)) {
            return back()->with('error', 'MIB file not found on disk.');
        }

        $content = file_get_contents($path);
        return view('admin.network.monitoring.mib_view', compact('mib', 'content'));
    }

    public function updateMibAssignment(Request $request, MonitoredHost $host)
    {
        $request->validate(['mib_id' => 'nullable|exists:mibs,id']);
        $host->update(['mib_id' => $request->mib_id]);

        return back()->with('success', 'MIB assigned to host successfully.');
    }

    public function discoverDevice(MonitoredHost $host)
    {
        dispatch_sync(new \App\Jobs\DiscoverSnmpDeviceJob($host));
        return back()->with('success', 'Device discovery completed synchronously.');
    }

    public function discoverInterfaces(MonitoredHost $host)
    {
        dispatch_sync(new \App\Jobs\DiscoverSnmpInterfacesJob($host));
        return back()->with('success', 'Interface discovery completed synchronously.');
    }

    public function pingHost(MonitoredHost $host, \App\Services\PingService $pingService)
    {
        try {
            $pingCount = $host->ping_packet_count ?? 3;
            $pingResult = $pingService->ping($host->ip, $pingCount);

            \App\Models\HostCheck::create([
                'host_id' => $host->id,
                'check_type' => 'ping',
                'latency_ms' => $pingResult['latency'],
                'packet_loss' => $pingResult['packet_loss'],
                'success' => $pingResult['success'],
            ]);

            if ($pingResult['success']) {
                $host->last_ping_at = now();
                if ($host->snmp_enabled && $host->last_snmp_at && $host->last_snmp_at->diffInMinutes(now()) > 3) {
                    $host->status = 'degraded';
                } else {
                    $host->status = 'up';
                }
            } else {
                $host->status = 'down';
            }
            $host->last_checked_at = now();
            $host->save();

            $statusText = $pingResult['success'] ? "Host is Up ({$pingResult['latency']}ms)" : "Host is Down";
            return back()->with('success', "Manual Ping Completed: $statusText");

        } catch (\Exception $e) {
            return back()->with('error', 'Ping failed: ' . $e->getMessage());
        }
    }
}
