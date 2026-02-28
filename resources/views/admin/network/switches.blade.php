@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-hdd-network me-2 text-primary"></i>Network Switches
        </h4>
        <small class="text-muted">
            All Meraki MS switches
            @if($lastSync)
                &bull; Last sync: <span class="font-monospace">{{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</span>
            @endif
        </small>
    </div>
    <div class="d-flex gap-2">
        @can('manage-network-settings')
        <form method="POST" action="{{ route('admin.network.sync') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i>Sync Now
            </button>
        </form>
        @endcan
    </div>
</div>

{{-- ── Filters ── --}}
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <select name="network" class="form-select form-select-sm">
            <option value="">All Networks</option>
            @foreach($networks as $net)
            <option value="{{ $net->network_id }}" {{ request('network') == $net->network_id ? 'selected' : '' }}>
                {{ $net->network_name ?: $net->network_id }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="online"   {{ request('status') == 'online'   ? 'selected' : '' }}>Online</option>
            <option value="offline"  {{ request('status') == 'offline'  ? 'selected' : '' }}>Offline</option>
            <option value="alerting" {{ request('status') == 'alerting' ? 'selected' : '' }}>Alerting</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
        <a href="{{ route('admin.network.switches') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        @if($switches->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-hdd-network display-4 mb-3 d-block"></i>
            No switches found. Run a sync or check your Meraki settings.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Status</th>
                        <th>Name</th>
                        <th>Model</th>
                        <th>Serial</th>
                        <th>IP</th>
                        <th>MAC</th>
                        <th>Network</th>
                        <th class="text-center">Ports</th>
                        <th class="text-center">Clients</th>
                        <th>Last Seen</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($switches as $sw)
                    <tr>
                        <td>
                            <span class="badge {{ $sw->statusBadgeClass() }}">
                                <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>{{ ucfirst($sw->status) }}
                            </span>
                        </td>
                        <td class="fw-semibold">{{ $sw->name ?: $sw->serial }}</td>
                        <td><span class="badge bg-secondary">{{ $sw->model }}</span></td>
                        <td class="font-monospace small text-muted">{{ $sw->serial }}</td>
                        <td class="font-monospace small">{{ $sw->lan_ip ?: '-' }}</td>
                        <td class="font-monospace small text-muted">{{ $sw->mac ?: '-' }}</td>
                        <td class="small text-muted">{{ $sw->network_name ?: $sw->network_id ?: '-' }}</td>
                        <td class="text-center"><span class="badge bg-secondary">{{ $sw->port_count }}</span></td>
                        <td class="text-center"><span class="badge bg-info text-dark">{{ $sw->clients_count }}</span></td>
                        <td class="small text-muted">
                            {{ $sw->last_reported_at ? $sw->last_reported_at->diffForHumans() : '-' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.network.switch-detail', $sw->serial) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-ethernet"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
