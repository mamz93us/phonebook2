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
        $host->load(['branch', 'vpnTunnel', 'metrics', 'snmpSensors']);
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
            'snmp_enabled'   => 'boolean',
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
            'snmp_enabled'   => 'boolean',
            'snmp_version'   => 'required_if:snmp_enabled,1|in:v1,v2c,v3',
            'snmp_community' => 'nullable|string',
        ]);

        $data = $request->all();
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
}
