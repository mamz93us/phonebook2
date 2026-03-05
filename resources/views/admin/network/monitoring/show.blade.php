@extends('layouts.admin')

@section('content')
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .glass-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
    }
    .sensor-value {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        letter-spacing: -0.02em;
    }
    .interface-pill {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-end">
    <div>
        <a href="{{ route('admin.network.monitoring.index') }}" class="btn btn-link link-secondary ps-0">
            <i class="bi bi-arrow-left me-1"></i> Back to Monitoring
        </a>
        <h2 class="h3 mt-2 mb-0 fw-bold">{{ $host->name }}</h2>
        <div class="d-flex align-items-center mt-2">
            @php
                $statusColors = [
                    'up' => 'success',
                    'down' => 'danger',
                    'degraded' => 'warning',
                    'unknown' => 'secondary'
                ];
                $color = $statusColors[$host->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $color }} me-3 shadow-sm">
                {{ strtoupper($host->status) }}
            </span>
            <code class="text-muted pe-3 border-end me-3 bg-light px-2 rounded">{{ $host->ip }}</code>
            <span class="text-muted small">Type: <span class="fw-bold text-dark">{{ strtoupper($host->type) }}</span></span>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if($host->snmp_enabled)
        <div class="input-group input-group-sm">
            <form action="{{ route('admin.network.monitoring.hosts.discover-device', $host) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-info border-end-0 rounded-start">
                    <i class="bi bi-search me-1"></i> Discover
                </button>
            </form>
            <form action="{{ route('admin.network.monitoring.hosts.discover-interfaces', $host) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary rounded-end">
                    <i class="bi bi-hdd-network me-1"></i> Interfaces
                </button>
            </form>
        </div>
        @endif
        @if($host->ping_enabled)
        <form action="{{ route('admin.network.monitoring.hosts.ping', $host) }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm" title="Manually ping this host now">
                <i class="bi bi-activity me-1"></i> Ping Now
            </button>
        </form>
        @endif
        <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSensorModal">
            <i class="bi bi-plus-lg me-1"></i> Add Sensor
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Latency Graph -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold">Latency & Availability</h5>
                <span class="text-muted small">Real-time Ping Statistics (24h)</span>
            </div>
            <div class="card-body pt-0">
                <div class="position-relative w-100" style="height: 250px;">
                    <canvas id="latencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Host Info & Stats -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 text-white p-2" style="background: linear-gradient(135deg, #212529 0%, #343a40 100%);">
            <div class="card-body">
                <h6 class="text-white-50 small text-uppercase mb-4 opacity-75">Connectivity Performance</h6>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="opacity-75">Avg Latency</span>
                    <span class="h3 mb-0 fw-bold sensor-value text-info">{{ round($host->hostChecks->avg('latency_ms') ?? 0, 1) }}<small class="fs-6 ms-1 fw-normal opacity-50">ms</small></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="opacity-75">Packet Loss</span>
                    <span class="h3 mb-0 fw-bold sensor-value text-{{ $host->hostChecks->avg('packet_loss') > 5 ? 'danger' : 'success' }}">
                        {{ round($host->hostChecks->avg('packet_loss') ?? 0, 1) }}<small class="fs-6 ms-1 fw-normal opacity-50">%</small>
                    </span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 bg-light-subtle">
            <div class="card-header bg-transparent py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold text-muted text-uppercase small">Assigned MIB</h6>
                <div>
                    <form action="{{ route('admin.network.monitoring.hosts.force-poll', $host) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-info me-2">
                            <i class="bi bi-arrow-clockwise me-1"></i> Poll Now
                        </button>
                    </form>
                    @if($host->mib_id)
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignMibModal">
                        <i class="bi bi-plus-circle me-1"></i> Link Sensors from MIB
                    </button>
                    @else
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignMibModal">
                        <i class="bi bi-link me-1"></i> Assign MIB
                    </button>
                    @endif
                </div>
            </div>
            <div class="card-body pt-0">
                @if($host->mib)
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-file-earmark-code fs-4 text-primary me-2"></i>
                        <div>
                            <div class="fw-bold">{{ $host->mib->name }}</div>
                            <div class="x-small text-muted">{{ basename($host->mib->file_path) }}</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.network.monitoring.mibs.view', $host->mib) }}" class="btn btn-outline-info btn-xs w-100 mt-2">
                        <i class="bi bi-eye me-1"></i> Preview OIDs
                    </a>
                @else
                    <div class="text-center py-3">
                        <i class="bi bi-file-earmark-x fs-2 text-muted opacity-25 d-block mb-1"></i>
                        <span class="x-small text-muted">No custom MIB linked</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 bg-light-subtle">
            <div class="card-header bg-transparent py-3 border-0">
                <h6 class="card-title mb-0 fw-bold text-muted text-uppercase small">Inventory Assets</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush small">
                    <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-3">
                        <div>
                            <span class="text-muted small d-block">Assigned MIB</span>
                            <span class="fw-bold">{{ $host->mib->name ?? 'None' }}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#assignMibModal">
                            {{ $host->mib_id ? 'Change' : 'Link MIB' }}
                        </button>
                    </div>

                    @if(!empty($discoveredObjects))
                    <div class="list-group-item bg-transparent border-0 px-3 pb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase">MIB Object Explorer</span>
                            <span class="badge bg-info-subtle text-info">{{ count($discoveredObjects) }} Objects</span>
                        </div>
                        <div class="alert alert-warning py-1 small mb-2 border-0" style="background:rgba(255,193,7,0.1)">
                            <i class="bi bi-info-circle me-1"></i> Select objects to add them as monitored sensors.
                        </div>
                        <div class="overflow-auto border rounded bg-white" style="max-height: 350px;">
                            <form action="{{ route('admin.network.monitoring.hosts.mib-sensors.store', $host) }}" method="POST" id="mibSensorsForm">
                                @csrf
                                <table class="table table-sm table-hover mb-0 small">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="30"></th>
                                            <th>Object Name</th>
                                            <th>ID</th>
                                            <th width="80">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($discoveredObjects as $index => $obj)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="sensors[{{ $index }}][enabled]" value="1" class="form-check-input ms-1">
                                                <input type="hidden" name="sensors[{{ $index }}][oid]" value="{{ $obj['oid_suffix'] }}">
                                                <input type="hidden" name="sensors[{{ $index }}][name]" value="{{ $obj['name'] }}">
                                            </td>
                                            <td><span class="fw-bold text-dark">{{ $obj['name'] }}</span></td>
                                            <td class="text-muted font-monospace">{{ $obj['oid_suffix'] }}</td>
                                            <td>
                                                <select name="sensors[{{ $index }}][data_type]" class="form-select form-select-sm py-0 x-small" style="height:22px">
                                                    <option value="gauge">Gauge</option>
                                                    <option value="counter">Counter</option>
                                                    <option value="uptime">Uptime</option>
                                                    <option value="boolean">Boolean</option>
                                                </select>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                        </div>
                        <button type="submit" form="mibSensorsForm" class="btn btn-primary btn-sm w-100 mt-2 shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Selected Sensors
                        </button>
                    </div>
                    @endif
                    <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-3">
                        <div>
                            <span class="fw-bold d-block">System Uptime</span>
                            <span class="text-muted x-small">RFC1213-MIB (.1.3.1.2.1.1.3.0)</span>
                        </div>
                        <span class="badge bg-white shadow-sm text-dark border">Native</span>
                    </div>
                    @foreach($host->snmpSensors as $sensor)
                        <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-3">
                            <div>
                                <span class="fw-bold d-block">{{ $sensor->name ?: 'Unnamed' }}</span>
                                <span class="text-muted x-small text-truncate d-inline-block" style="max-width: 150px;">{{ $sensor->oid }}</span>
                            </div>
                            <form action="{{ route('admin.network.monitoring.hosts.sensors.destroy', [$host, $sensor]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove sensor?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete Sensor"><i class="bi bi-trash-fill opacity-25"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $groupedSensors = ['General' => [], 'Interfaces' => []];
    foreach($host->snmpSensors->where('graph_enabled', true) as $sensor) {
        $name = $sensor->name ?: $sensor->oid;
        if (preg_match('/^(.*)\s+-\s+(Traffic In|Traffic Out|Status)$/', $name, $matches)) {
            $interfaceName = $matches[1];
            $type = $matches[2];
            $groupedSensors['Interfaces'][$interfaceName][$type] = $sensor;
        } else {
            $groupedSensors['General'][] = $sensor;
        }
    }
@endphp

<!-- General Sensors -->
@if(!empty($groupedSensors['General']))
<h6 class="text-uppercase text-muted fw-bold small mb-3">Host System Sensors</h6>
<div class="row g-4 mb-5">
    @foreach($groupedSensors['General'] as $sensor)
        @php $latest = $sensor->sensorMetrics->last(); @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 glass-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="card-title mb-0 text-muted fw-bold small text-uppercase">{{ $sensor->name }}</h6>
                            <span class="badge bg-light text-muted fw-normal interface-pill border">{{ $sensor->data_type }}</span>
                        </div>
                        @if($latest)
                            <div class="text-end">
                                <div class="text-muted x-small">Last polled: {{ $latest->recorded_at->diffForHumans() }}</div>
                            </div>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary small">No Data</span>
                        @endif
                    </div>
                    
                    <div class="mt-auto">
                        @if($latest)
                            @if($sensor->data_type === 'uptime')
                                {{-- UPTIME DISPLAY --}}
                                @php
                                    $rawVal = $latest->value;
                                    $unit = strtolower(trim($sensor->unit));
                                    
                                    // By default, system uptimes are returned as Timeticks (centiseconds)
                                    // If the user explicitly set unit to 's' or 'seconds', we treat it as seconds.
                                    // Otherwise, we divide by 100.
                                    $isExplicitSeconds = in_array($unit, ['s', 'sec', 'seconds', 'second']);
                                    $totalSeconds = $isExplicitSeconds ? (int)$rawVal : (int)($rawVal / 100);
                                    
                                    $days = (int)floor($totalSeconds / 86400);
                                    $hours = (int)floor(($totalSeconds % 86400) / 3600);
                                    $mins = (int)floor(($totalSeconds % 3600) / 60);
                                @endphp
                                <div class="text-center py-3">
                                    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                                        <div class="text-center">
                                            <div class="display-6 fw-bold text-primary">{{ $days }}</div>
                                            <div class="text-muted small">days</div>
                                        </div>
                                        <span class="display-6 text-muted opacity-25">:</span>
                                        <div class="text-center">
                                            <div class="display-6 fw-bold text-primary">{{ $hours }}</div>
                                            <div class="text-muted small">hrs</div>
                                        </div>
                                        <span class="display-6 text-muted opacity-25">:</span>
                                        <div class="text-center">
                                            <div class="display-6 fw-bold text-primary">{{ $mins }}</div>
                                            <div class="text-muted small">min</div>
                                        </div>
                                    </div>
                                    <div class="text-muted small"><i class="bi bi-clock-history me-1"></i> System Uptime</div>
                                </div>

                            @elseif($sensor->data_type === 'boolean')
                                {{-- BOOLEAN DISPLAY --}}
                                @php
                                    $isOk = $latest->value == 1; // Assuming 1=OK/UP, 0=FAIL/DOWN
                                @endphp
                                <div class="text-center py-4">
                                    @if($isOk)
                                        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-2" style="width: 60px; height: 60px;">
                                            <i class="bi bi-check-lg fs-1"></i>
                                        </div>
                                        <div class="fw-bold text-success fs-5">Status OK</div>
                                    @else
                                        <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-2" style="width: 60px; height: 60px;">
                                            <i class="bi bi-x-lg fs-1"></i>
                                        </div>
                                        <div class="fw-bold text-danger fs-5">Status Critical</div>
                                    @endif
                                </div>

                            @elseif($sensor->data_type === 'counter')
                                {{-- COUNTER DISPLAY (Shows Rate) --}}
                                <div class="text-center py-3">
                                    <span class="display-5 fw-bold text-info">
                                        {{ number_format($latest->value, 2) }}
                                    </span>
                                    <span class="fs-5 text-muted ms-1">{{ $sensor->unit ?: 'units/sec' }}</span>
                                </div>
                                <div style="height: 60px;">
                                    <canvas id="chart-sensor-{{ $sensor->id }}"></canvas>
                                </div>

                            @else
                                {{-- GAUGE DISPLAY --}}
                                <div class="text-center mb-2">
                                    <span class="h2 fw-bold text-dark">
                                        {{ number_format($latest->value, 1) }}
                                    </span>
                                    <span class="fs-6 text-muted ms-1">{{ $sensor->unit }}</span>
                                </div>
                                <div style="height: 80px;">
                                    <canvas id="chart-sensor-{{ $sensor->id }}"></canvas>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4 text-muted opacity-50">
                                <i class="bi bi-hourglass-split fs-2 d-block mb-2"></i>
                                <small>Waiting for initial poll...</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<!-- Interface Sensors -->
@if(!empty($groupedSensors['Interfaces']))
<h6 class="text-uppercase text-muted fw-bold small mb-3">Network Interfaces</h6>
<div class="row g-3">
    @foreach($groupedSensors['Interfaces'] as $iface => $sensors)
        <div class="col-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-3 bg-light p-4 border-end d-flex flex-column justify-content-center">
                            <h5 class="fw-bold mb-1 text-dark">{{ $iface }}</h5>
                            @if(isset($sensors['Status']))
                                @php $statusVal = $sensors['Status']->sensorMetrics->last()?->value; @endphp
                                <span class="badge bg-{{ $statusVal == 1 ? 'success' : 'danger' }}-subtle text-{{ $statusVal == 1 ? 'success' : 'danger' }} d-inline-block align-self-start mb-3">
                                    <i class="bi bi-circle-fill me-1 small"></i> {{ $statusVal == 1 ? 'UP' : 'DOWN' }}
                                </span>
                            @endif
                            <div class="mt-auto">
                                @if(isset($sensors['Traffic In']))
                                    @php
                                        $inVal = $sensors['Traffic In']->sensorMetrics->last()?->value ?? 0;
                                        $inFormatted = $inVal > 1048576 ? number_format($inVal / 1048576, 2) . ' MB/s' : ($inVal > 1024 ? number_format($inVal / 1024, 2) . ' KB/s' : number_format($inVal, 0) . ' B/s');
                                    @endphp
                                    <div class="small text-muted mb-1">Inbound Traffic</div>
                                    <div class="h5 fw-bold sensor-value mb-3 text-info">
                                        {{ $inFormatted }}
                                    </div>
                                @endif
                                @if(isset($sensors['Traffic Out']))
                                    @php
                                        $outVal = $sensors['Traffic Out']->sensorMetrics->last()?->value ?? 0;
                                        $outFormatted = $outVal > 1048576 ? number_format($outVal / 1048576, 2) . ' MB/s' : ($outVal > 1024 ? number_format($outVal / 1024, 2) . ' KB/s' : number_format($outVal, 0) . ' B/s');
                                    @endphp
                                    <div class="small text-muted mb-1">Outbound Traffic</div>
                                    <div class="h5 fw-bold sensor-value text-primary">
                                        {{ $outFormatted }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-9 p-3">
                            <div class="row g-3">
                                @if(isset($sensors['Traffic In']))
                                <div class="col-md-6">
                                    <div class="text-muted small mb-2 fw-bold text-uppercase">Inbound Traffic</div>
                                    <div style="height: 100px;">
                                        <canvas id="chart-sensor-{{ $sensors['Traffic In']->id }}"></canvas>
                                    </div>
                                </div>
                                @endif
                                @if(isset($sensors['Traffic Out']))
                                <div class="col-md-6">
                                    <div class="text-muted small mb-2 fw-bold text-uppercase">Outbound Traffic</div>
                                    <div style="height: 100px;">
                                        <canvas id="chart-sensor-{{ $sensors['Traffic Out']->id }}"></canvas>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<!-- Assign MIB Modal -->
<div class="modal fade" id="assignMibModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.network.monitoring.hosts.mib-assign', $host) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title">Link Vendor MIB</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Select a MIB file that matches this device's manufacturer. This helps the discovery engine identify system sensors and interface descriptions properly.</p>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Available MIBs</label>
                        <select name="mib_id" class="form-select" required>
                            <option value="">-- No MIB --</option>
                            @foreach($mibs as $mib)
                                <option value="{{ $mib->id }}" {{ $host->mib_id == $mib->id ? 'selected' : '' }}>
                                    {{ $mib->name }} ({{ basename($mib->file_path) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light rounded-bottom">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Assignment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modals & Scripts remain similar but with improved logic -->
<div class="modal fade" id="addSensorModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.network.monitoring.hosts.sensors.store', $host) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title">Add Custom SNMP Sensor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small">Sensor Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Core CPU Usage" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">OID (Object Identifier)</label>
                            <input type="text" name="oid" class="form-control" placeholder="1.3.6.1.4.1.9.2.1.57" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Data Type</label>
                            <select name="data_type" class="form-select" required>
                                <option value="gauge">Gauge (CPU, RAM)</option>
                                <option value="counter">Counter (Traffic)</option>
                                <option value="rate">Rate (Packets/sec)</option>
                                <option value="temperature">Temperature</option>
                                <option value="uptime">Uptime</option>
                                <option value="boolean">Boolean (Status)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g. %, bytes, °C">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="graph_enabled" value="1" id="graphSwitch" checked>
                                <label class="form-check-label fw-bold ms-2" for="graphSwitch">Enable Graphing</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light rounded-bottom">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add Sensor</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6c757d';

    // 1. Latency Chart
    const latencyCtx = document.getElementById('latencyChart').getContext('2d');
    const latencyData = @json($host->hostChecks ? $host->hostChecks->take(144)->sortBy('checked_at')->values() : []);
    
    new Chart(latencyCtx, {
        type: 'line',
        data: {
            labels: latencyData.map(d => new Date(d.checked_at)),
            datasets: [{
                label: 'Latency (ms)',
                data: latencyData.map(d => ({x: new Date(d.checked_at), y: d.latency_ms})),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#212529',
                    padding: 12,
                    callbacks: {
                        label: ctx => `Latency: ${ctx.parsed.y} ms`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
                    ticks: { callback: v => v + ' ms' }
                },
                x: {
                    type: 'time',
                    time: { unit: 'hour' },
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Sensor Metric Charts
    @foreach($host->snmpSensors->where('graph_enabled', true) as $sensor)
        (function() {
            const ctx = document.getElementById('chart-sensor-{{ $sensor->id }}');
            if (!ctx) return;
            
            const sensorData = @json($sensor->sensorMetrics->map(fn($m) => ['t' => $m->recorded_at->toIso8601String(), 'y' => $m->value])->values());
            const color = '{{ str_contains($sensor->name, "Out") ? "#6610f2" : "#0dcaf0" }}';
            
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    datasets: [{
                        data: sensorData.map(d => ({x: new Date(d.t), y: d.y})),
                        borderColor: color,
                        backgroundColor: (c) => {
                            const chart = c.chart;
                            const {ctx, chartArea} = chart;
                            if (!chartArea) return null;
                            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                            gradient.addColorStop(0, 'rgba(255,255,255,0)');
                            gradient.addColorStop(1, color + '22');
                            return gradient;
                        },
                        fill: true,
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: true } },
                    scales: {
                        y: { display: false, beginAtZero: true },
                        x: { type: 'time', display: false }
                    }
                }
            });
        })();
    @endforeach
});
</script>
@endpush
@endsection
