@extends('layouts.admin')

@section('content')

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ route('admin.network.switches') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="badge {{ $switch->statusBadgeClass() }} fs-6">
                <i class="bi bi-circle-fill me-1" style="font-size:9px"></i>{{ ucfirst($switch->status) }}
            </span>
            <h4 class="mb-0 fw-bold">{{ $switch->name ?: $switch->serial }}</h4>
            <span class="badge bg-secondary">{{ $switch->model }}</span>
        </div>
        <small class="text-muted">
            Serial: <code>{{ $switch->serial }}</code>
            @if($switch->lan_ip) &bull; IP: <code>{{ $switch->lan_ip }}</code>@endif
            @if($switch->mac) &bull; MAC: <code>{{ $switch->mac }}</code>@endif
            @if($switch->firmware) &bull; Firmware: <code>{{ $switch->firmware }}</code>@endif
            @if($switch->last_reported_at) &bull; Last seen: {{ $switch->last_reported_at->diffForHumans() }}@endif
        </small>
    </div>
</div>

{{-- ── Port Legend ── --}}
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center small">
    <span class="fw-semibold text-muted me-1">Legend:</span>
    <span class="d-inline-flex align-items-center gap-1">
        <span style="width:18px;height:18px;border-radius:3px;background:#198754;display:inline-block;"></span> Connected
    </span>
    <span class="d-inline-flex align-items-center gap-1">
        <span style="width:18px;height:18px;border-radius:3px;background:#6c757d;opacity:.5;display:inline-block;"></span> Disconnected
    </span>
    <span class="d-inline-flex align-items-center gap-1">
        <span style="width:18px;height:18px;border-radius:3px;background:#0d6efd;opacity:.75;display:inline-block;"></span> Uplink
    </span>
    <span class="d-inline-flex align-items-center gap-1">
        <span style="width:18px;height:18px;border-radius:3px;background:#dee2e6;display:inline-block;border:1px solid #adb5bd"></span> Disabled
    </span>
</div>

{{-- ── Port Grid Visualisation ── --}}
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-ethernet me-2"></i>Port Panel</h6>
        <small class="text-muted">
            {{ $ports->where('status', 'Connected')->count() }} connected /
            {{ $ports->count() }} total
        </small>
    </div>
    <div class="card-body py-3">
        @if($ports->isEmpty())
        <div class="text-center text-muted py-3">
            <i class="bi bi-ethernet display-4 d-block mb-2"></i>No port data. Run a sync to fetch port information.
        </div>
        @else
        {{-- Switch body mockup: two rows of ports (top & bottom alternating) --}}
        <div class="d-flex flex-wrap gap-1 justify-content-start" id="portGrid">
            @foreach($ports as $port)
            @php
                $tileBg    = $port->tileBgClass();
                $textColor = str_contains($tileBg, 'bg-success') || str_contains($tileBg, 'bg-primary')
                    ? 'text-white'
                    : 'text-dark';
                $tooltip   = $port->label();
                if ($port->client_mac) $tooltip .= ' | ' . $port->client_mac;
                if ($port->speed) $tooltip .= ' | ' . $port->speedLabel();
                if ($port->vlan) $tooltip .= ' | VLAN ' . $port->vlan;
            @endphp
            <div class="port-tile {{ $tileBg }} {{ $textColor }} rounded"
                 style="width:42px;height:48px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;font-size:10px;font-weight:600;border:1px solid rgba(0,0,0,.1);"
                 data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip }}"
                 data-port-id="{{ $port->port_id }}"
                 onclick="showPortDetail({{ $port->id }})">
                @if($port->is_uplink)
                <i class="bi bi-arrow-up-circle" style="font-size:14px"></i>
                @else
                <i class="bi bi-ethernet" style="font-size:14px;opacity:.8"></i>
                @endif
                <div style="font-size:9px;line-height:1;margin-top:2px">{{ $port->port_id }}</div>
            </div>
            @endforeach
        </div>

        {{-- Port detail panel (shown on click) --}}
        <div id="portDetailPanel" class="mt-3 collapse">
            <div class="card border-primary">
                <div class="card-body py-2 small" id="portDetailContent">
                    <span class="text-muted">Click a port to see details.</span>
                </div>
            </div>
        </div>

        {{-- Serialised port data for JS ── --}}
        <script id="portData" type="application/json">
        {!! json_encode($ports->map(fn($p) => [
            'id'              => $p->id,
            'port_id'         => $p->port_id,
            'name'            => $p->name,
            'enabled'         => $p->enabled,
            'type'            => $p->type,
            'vlan'            => $p->vlan,
            'allowed_vlans'   => $p->allowed_vlans,
            'poe_enabled'     => $p->poe_enabled,
            'is_uplink'       => $p->is_uplink,
            'status'          => $p->status,
            'speed'           => $p->speedLabel(),
            'duplex'          => $p->duplex,
            'client_mac'      => $p->client_mac,
            'client_hostname' => $p->client_hostname,
        ])->values()) !!}
        </script>
        @endif
    </div>
</div>

{{-- ── Summary stats ── --}}
<div class="row g-3 mb-4">
    @php
        $connected    = $ports->where('status', 'Connected')->count();
        $disconnected = $ports->where('status', 'Disconnected')->count();
        $uplinks      = $ports->where('is_uplink', true)->count();
        $poe          = $ports->where('poe_enabled', true)->count();
    @endphp
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="h3 fw-bold text-success mb-0">{{ $connected }}</div>
                <div class="small text-muted">Connected</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="h3 fw-bold text-secondary mb-0">{{ $disconnected }}</div>
                <div class="small text-muted">Disconnected</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="h3 fw-bold text-primary mb-0">{{ $uplinks }}</div>
                <div class="small text-muted">Uplinks</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="h3 fw-bold text-warning mb-0">{{ $poe }}</div>
                <div class="small text-muted">PoE Ports</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Port detail table ── --}}
@if($ports->isNotEmpty())
<div class="card shadow-sm mb-4">
    <div class="card-header py-2">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Port Details</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Port</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Speed</th>
                        <th>Type</th>
                        <th>VLAN</th>
                        <th>PoE</th>
                        <th>Client MAC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ports as $port)
                    <tr>
                        <td class="font-monospace fw-semibold">
                            @if($port->is_uplink)<i class="bi bi-arrow-up-circle text-primary me-1"></i>@endif
                            {{ $port->port_id }}
                        </td>
                        <td>{{ $port->name ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $port->status === 'Connected' ? 'bg-success' : ($port->enabled ? 'bg-secondary' : 'bg-secondary bg-opacity-50') }} small">
                                {{ $port->status ?: ($port->enabled ? 'Unknown' : 'Disabled') }}
                            </span>
                        </td>
                        <td class="font-monospace small">{{ $port->isConnected() ? $port->speedLabel() : '-' }}</td>
                        <td class="text-muted small">{{ $port->type ?: '-' }}</td>
                        <td>
                            @if($port->vlan)
                            <span class="badge bg-info text-dark small">{{ $port->vlan }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($port->poe_enabled)
                            <i class="bi bi-lightning-fill text-warning" title="PoE enabled"></i>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="font-monospace text-muted small">{{ $port->client_mac ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Connected Clients ── --}}
@if($clients->isNotEmpty())
<div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-laptop me-2"></i>Connected Clients</h6>
        <span class="badge bg-secondary">{{ $clients->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:300px;overflow-y:auto">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Status</th>
                        <th>Hostname</th>
                        <th>IP</th>
                        <th>MAC</th>
                        <th>Manufacturer</th>
                        <th>VLAN</th>
                        <th>Port</th>
                        <th>Usage</th>
                        <th>Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                    <tr>
                        <td><span class="badge {{ $client->statusBadgeClass() }} small">{{ $client->status ?: '-' }}</span></td>
                        <td class="fw-semibold">{{ $client->hostname ?: '-' }}</td>
                        <td class="font-monospace small">{{ $client->ip ?: '-' }}</td>
                        <td class="font-monospace text-muted small">{{ $client->mac }}</td>
                        <td class="text-muted">{{ $client->manufacturer ?: '-' }}</td>
                        <td>
                            @if($client->vlan)<span class="badge bg-info text-dark small">{{ $client->vlan }}</span>@else<span class="text-muted">-</span>@endif
                        </td>
                        <td class="font-monospace small">{{ $client->port_id ?: '-' }}</td>
                        <td class="font-monospace small text-end">{{ $client->usageLabel() }}</td>
                        <td class="text-muted small">{{ $client->last_seen ? $client->last_seen->diffForHumans() : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// Initialise Bootstrap tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
});

// Port data from server
const portData = JSON.parse(document.getElementById('portData')?.textContent || '[]');

function showPortDetail(portId) {
    const port = portData.find(p => p.id === portId);
    if (!port) return;

    const panel   = document.getElementById('portDetailPanel');
    const content = document.getElementById('portDetailContent');

    const status = port.status || (port.enabled ? 'Unknown' : 'Disabled');
    const vlans  = port.allowed_vlans || (port.vlan ? String(port.vlan) : '—');

    content.innerHTML = `
        <div class="row g-2">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0 small">
                    <tr><th class="text-muted ps-0" style="width:40%">Port</th><td class="font-monospace fw-bold">${port.port_id}</td></tr>
                    <tr><th class="text-muted ps-0">Name</th><td>${port.name || '—'}</td></tr>
                    <tr><th class="text-muted ps-0">Status</th><td><span class="badge ${status === 'Connected' ? 'bg-success' : 'bg-secondary'}">${status}</span></td></tr>
                    <tr><th class="text-muted ps-0">Speed</th><td class="font-monospace">${port.status === 'Connected' ? port.speed : '—'}</td></tr>
                    <tr><th class="text-muted ps-0">Duplex</th><td>${port.duplex || '—'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0 small">
                    <tr><th class="text-muted ps-0" style="width:40%">Type</th><td>${port.type || '—'}</td></tr>
                    <tr><th class="text-muted ps-0">VLAN</th><td class="font-monospace">${port.vlan || '—'}</td></tr>
                    <tr><th class="text-muted ps-0">Allowed VLANs</th><td class="font-monospace small">${vlans}</td></tr>
                    <tr><th class="text-muted ps-0">PoE</th><td>${port.poe_enabled ? '<i class="bi bi-lightning-fill text-warning"></i> Enabled' : '—'}</td></tr>
                    <tr><th class="text-muted ps-0">Uplink</th><td>${port.is_uplink ? '<i class="bi bi-arrow-up-circle text-primary"></i> Yes' : '—'}</td></tr>
                </table>
            </div>
            ${port.client_mac ? `
            <div class="col-12 mt-1">
                <div class="alert alert-success py-1 small mb-0">
                    <i class="bi bi-laptop me-1"></i>
                    Client: <code>${port.client_mac}</code>
                    ${port.client_hostname ? '&nbsp;&bull;&nbsp;' + port.client_hostname : ''}
                </div>
            </div>` : ''}
        </div>
    `;

    if (!panel.classList.contains('show')) {
        new bootstrap.Collapse(panel, { show: true });
    }

    // Highlight selected port tile
    document.querySelectorAll('.port-tile').forEach(el => el.style.outline = '');
    const tile = document.querySelector(`.port-tile[data-port-id="${port.port_id}"]`);
    if (tile) tile.style.outline = '3px solid #0d6efd';
}
</script>
@endpush
