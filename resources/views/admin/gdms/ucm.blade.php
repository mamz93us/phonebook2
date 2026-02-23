@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-hdd-network me-2 text-primary"></i>UCM Devices — GDMS
        </h4>
        <small class="text-muted">Live status from Grandstream Device Management System (On-Premise PBX)</small>
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
        <strong>No UCM devices returned.</strong>
        The API was reached (projectId=3, UCM project) but returned 0 devices.
        Make sure GDMS credentials and org ID are correct.
    </div>

    @if(!empty($rawDebug))
    <div class="card border-warning mt-3">
        <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">
                <i class="bi bi-bug me-1"></i>/device/list (projectId=1) returned {{ count($rawDebug) }} device(s) — showing raw data
            </span>
            <button class="btn btn-sm btn-outline-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#debugPanel">Toggle</button>
        </div>
        <div id="debugPanel" class="collapse show">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 small font-monospace">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                @foreach(array_keys($rawDebug[0] ?? []) as $col)
                                    <th>{{ $col }}</th>
                                @endforeach
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
    @endif

@else
    {{-- ── Summary strip ── --}}
    @php
        $cOnline   = collect($devices)->filter(fn($d) => $d['deviceStatus'] === 1)->count();
        $cOffline  = collect($devices)->filter(fn($d) => $d['deviceStatus'] === 0)->count();
        $cAbnormal = collect($devices)->filter(fn($d) => $d['deviceStatus'] === -1)->count();
    @endphp
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-success fs-6 px-3 py-2">
            <i class="bi bi-circle-fill me-1" style="font-size:9px"></i>{{ $cOnline }} Online
        </span>
        <span class="badge bg-danger fs-6 px-3 py-2">
            <i class="bi bi-circle-fill me-1" style="font-size:9px"></i>{{ $cOffline }} Offline
        </span>
        @if($cAbnormal)
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $cAbnormal }} Abnormal
        </span>
        @endif
        <span class="badge bg-secondary fs-6 px-3 py-2">{{ count($devices) }} Total</span>
    </div>

    {{-- ── Device cards ── --}}
    <div class="row g-3">
        @foreach($devices as $i => $device)
        @php
            $ds = $device['deviceStatus'];
            $badgeClass = match(true) {
                $ds === 1  => 'bg-success',
                $ds === -1 => 'bg-warning text-dark',
                default    => 'bg-danger',
            };
            $statusLabel = match(true) {
                $ds === 1  => 'Online',
                $ds === -1 => 'Abnormal',
                default    => 'Offline',
            };
            $borderClass = match(true) {
                $ds === 1  => 'border-success',
                $ds === -1 => 'border-warning',
                default    => 'border-danger',
            };
            $raw = $device['_raw'];
        @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-2 {{ $borderClass }}">
                <div class="card-header d-flex align-items-center gap-2 py-2">
                    <span class="badge {{ $badgeClass }} px-2 py-1">
                        <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>{{ $statusLabel }}
                    </span>
                    <strong class="ms-1 flex-grow-1 text-truncate" title="{{ $device['deviceName'] }}">
                        {{ $device['deviceName'] }}
                    </strong>
                    <span class="badge bg-primary bg-opacity-75 small">{{ $device['productName'] }}</span>
                </div>
                <div class="card-body py-2 px-3">
                    <table class="table table-sm table-borderless mb-0 small">
                        <tbody>
                            <tr>
                                <th class="text-muted ps-0" style="width:40%">MAC</th>
                                <td class="font-monospace">{{ $device['mac'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Local IP</th>
                                <td class="font-monospace">{{ $device['deviceIp'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Firmware</th>
                                <td>{{ $device['firmwareVersion'] }}</td>
                            </tr>
                            @if($device['lastOnlineTime'])
                            <tr>
                                <th class="text-muted ps-0">Last Seen</th>
                                <td class="text-muted">{{ $device['lastOnlineTime'] }}</td>
                            </tr>
                            @endif

                            {{-- Show all extra fields from _raw that aren't already shown --}}
                            @php
                                $shownKeys = ['deviceName','productName','mac','macAddr','deviceIp','ip','localIp',
                                              'firmwareVersion','firmware','version','online','isOnline',
                                              'deviceStatus','status','name','ucmName','model','deviceModel',
                                              'lastOnlineTime','lastOnlineAt','updateTime'];
                                $extras = array_filter($raw, fn($v, $k) =>
                                    !in_array($k, $shownKeys) && !is_array($v) && $v !== null && $v !== '',
                                    ARRAY_FILTER_USE_BOTH
                                );
                            @endphp
                            @foreach($extras as $key => $value)
                            <tr>
                                <th class="text-muted ps-0">{{ $key }}</th>
                                <td class="text-truncate font-monospace" style="max-width:160px" title="{{ $value }}">
                                    {{ $value }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
