@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-hdd-network me-2 text-primary"></i>UCM Devices — GDMS
        </h4>
        <small class="text-muted">Live status from Grandstream Device Management System</small>
    </div>
    <a href="{{ route('admin.gdms.ucm') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
    </a>
</div>

@if($error)
    <div class="alert alert-danger d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Could not reach GDMS API</strong><br>
            <span class="small font-monospace">{{ $error }}</span>
        </div>
    </div>
@elseif($devices !== null && count($devices) === 0)
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>No UCM devices found in GDMS.
    </div>
@else
    {{-- Summary badges --}}
    @php
        $online  = collect($devices)->where('online', 1)->count();
        $offline = collect($devices)->where('online', 0)->count();
    @endphp
    <div class="d-flex gap-3 mb-3">
        <span class="badge bg-success fs-6 px-3 py-2">
            <i class="bi bi-circle-fill me-1" style="font-size:10px"></i>{{ $online }} Online
        </span>
        <span class="badge bg-danger fs-6 px-3 py-2">
            <i class="bi bi-circle-fill me-1" style="font-size:10px"></i>{{ $offline }} Offline
        </span>
        <span class="badge bg-secondary fs-6 px-3 py-2">
            {{ count($devices) }} Total
        </span>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Status</th>
                            <th>Device Name</th>
                            <th>Model</th>
                            <th>MAC Address</th>
                            <th>Local IP</th>
                            <th>Firmware</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $i => $device)
                        @php
                            $isOnline = ($device['online'] ?? 0) == 1;
                        @endphp
                        <tr class="{{ $isOnline ? '' : 'table-danger bg-opacity-25' }}">
                            <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td>
                                @if($isOnline)
                                    <span class="badge bg-success">
                                        <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>Online
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>Offline
                                    </span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $device['deviceName'] ?? '—' }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-75">
                                    {{ $device['productName'] ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <code class="small">{{ $device['mac'] ?? '—' }}</code>
                            </td>
                            <td>
                                <code class="small text-muted">{{ $device['deviceIp'] ?? '—' }}</code>
                            </td>
                            <td class="small text-muted">
                                {{ $device['firmwareVersion'] ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
