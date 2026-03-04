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
        $hosts = MonitoredHost::with(['branch', 'vpnTunnel'])->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $tunnels = VpnTunnel::orderBy('name')->get();
        
        return view('admin.network.monitoring.index', compact('hosts', 'branches', 'tunnels'));
    }

    public function show(MonitoredHost $host)
    {
        $host->load([
            'branch', 
            'vpnTunnel', 
            'hostChecks' => fn($q) => $q->latest('checked_at')->take(50),
            'snmpSensors.sensorMetrics' => fn($q) => $q->where('recorded_at', '>=', now()->subHours(24))->orderBy('recorded_at')
        ]);
        return view('admin.network.monitoring.show', compact('host'));
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
            'snmp_enabled'   => 'boolean',
            'snmp_port'      => 'nullable|integer',
            'snmp_version'   => 'required_if:snmp_enabled,1|in:v1,v2c,v3',
            'snmp_community' => 'required_if:snmp_enabled,1|string',
        ]);

        MonitoredHost::create($request->all());

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
            'snmp_enabled'   => 'boolean',
            'snmp_port'      => 'nullable|integer',
            'snmp_version'   => 'required_if:snmp_enabled,1|in:v1,v2c,v3',
            'snmp_community' => 'nullable|string',
        ]);

        $data = $request->except(['_token', '_method']);
        $data['ping_enabled'] = $request->boolean('ping_enabled', false);
        $data['snmp_enabled'] = $request->boolean('snmp_enabled', false);
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

    public function discoverDevice(MonitoredHost $host)
    {
        \App\Jobs\DiscoverSnmpDeviceJob::dispatch($host);
        return back()->with('success', 'Device discovery job dispatched in the background.');
    }

    public function discoverInterfaces(MonitoredHost $host)
    {
        \App\Jobs\DiscoverSnmpInterfacesJob::dispatch($host);
        return back()->with('success', 'Interface discovery job dispatched in the background.');
    }
}
