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
        <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
        <div>
            <strong>Could not reach GDMS API</strong><br>
            <span class="small font-monospace">{{ $error }}</span>
        </div>
    </div>

@elseif($devices !== null && count($devices) === 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-circle me-2"></i>
        <strong>No UCM / On-Premise PBX devices found.</strong>
        The API was reached but returned 0 devices after checking both
        <code>/v1.0.0/ucm/list</code> and <code>/v1.0.0/device/list</code>.
    </div>

    {{-- Debug panel: show what /device/list actually returned --}}
    @if(!empty($rawDebug))
    <div class="card border-warning mt-3">
        <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">
                <i class="bi bi-bug me-1"></i>Debug — /device/list returned {{ count($rawDebug) }} device(s)
            </span>
            <button class="btn btn-sm btn-outline-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#debugPanel">
                Show / Hide
            </button>
        </div>
        <div id="debugPanel" class="collapse">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 small font-monospace">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                @if(!empty($rawDebug[0]))
                                    @foreach(array_keys($rawDebug[0]) as $col)
                                        <th>{{ $col }}</th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rawDebug as $i => $row)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                @foreach($row as $val)
                                    <td>{{ is_array($val) ? json_encode($val) : $val }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-secondary mt-2 small">
        <i class="bi bi-info-circle me-1"></i>
        <code>/device/list</code> also returned 0 results.
        Make sure GDMS credentials (GDMS_USERNAME, GDMS_PASSWORD_HASH, org ID) are correct in <code>.env</code>.
    </div>
    @endif

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
                        @php $isOnline = ($device['online'] ?? 0) == 1; @endphp
                        <tr class="{{ $isOnline ? '' : 'table-danger bg-opacity-10' }}">
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
                            <td><strong>{{ $device['deviceName'] }}</strong></td>
                            <td>
                                <span class="badge bg-primary bg-opacity-75">{{ $device['productName'] }}</span>
                            </td>
                            <td><code class="small">{{ $device['mac'] }}</code></td>
                            <td><code class="small text-muted">{{ $device['deviceIp'] }}</code></td>
                            <td class="small text-muted">{{ $device['firmwareVersion'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
