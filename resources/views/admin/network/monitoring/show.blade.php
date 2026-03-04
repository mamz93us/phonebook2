@extends('layouts.admin')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-end">
    <div>
        <a href="{{ route('admin.network.monitoring.index') }}" class="btn btn-link link-secondary ps-0">
            <i class="bi bi-arrow-left me-1"></i> Back to Monitoring
        </a>
        <h2 class="h3 mt-2 mb-0">{{ $host->name }}</h2>
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
            <span class="badge bg-{{ $color }} me-3">
                {{ strtoupper($host->status) }}
            </span>
            <code class="text-muted pe-3 border-end me-3">{{ $host->ip }}</code>
            <span class="text-muted small">Type: <span class="fw-bold text-dark">{{ strtoupper($host->type) }}</span></span>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if($host->snmp_enabled)
        <form action="{{ route('admin.network.monitoring.hosts.discover-device', $host) }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-outline-info btn-sm">
                <i class="bi bi-search me-1"></i> Discover Device
            </button>
        </form>
        <form action="{{ route('admin.network.monitoring.hosts.discover-interfaces', $host) }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-hdd-network me-1"></i> Interfaces
            </button>
        </form>
        @endif
        @if($host->ping_enabled)
        <form action="{{ route('admin.network.monitoring.hosts.ping', $host) }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-outline-success btn-sm" title="Manually ping this host now">
                <i class="bi bi-activity me-1"></i> Ping Now
            </button>
        </form>
        @endif
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSensorModal">
            <i class="bi bi-plus-lg me-1"></i> Add Custom Sensor
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Latency Graph -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0">Latency & Availability (Last 24h)</h5>
            </div>
            <div class="card-body">
                <canvas id="latencyChart" style="min-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Host Info & Stats -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 text-white bg-dark">
            <div class="card-body">
                <h6 class="text-white-50 small text-uppercase mb-3">Host Connectivity</h6>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span>Average Latency</span>
                    <span class="h4 mb-0 fw-bold">{{ round($host->hostChecks->avg('latency_ms') ?? 0, 2) }}ms</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Packet Loss</span>
                    <span class="h4 mb-0 fw-bold text-{{ $host->hostChecks->avg('packet_loss') > 5 ? 'danger' : 'success' }}">
                        {{ round($host->hostChecks->avg('packet_loss') ?? 0, 1) }}%
                    </span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0">Active SNMP Sensors</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block">System Uptime</span>
                            <span class="text-muted small">.1.3.6.1.2.1.1.3.0</span>
                        </div>
                        <span class="badge bg-light text-dark">Default</span>
                    </li>
                    @foreach($host->snmpSensors as $sensor)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold d-block">{{ $sensor->name ?: 'Unnamed Sensor' }}</span>
                                <span class="text-muted small">{{ $sensor->oid }} <span class="badge bg-light text-secondary ms-1">{{ $sensor->data_type }}</span></span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <form action="{{ route('admin.network.monitoring.hosts.sensors.destroy', [$host, $sensor]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this sensor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link text-danger p-0 ms-2"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Metric Graphs for Clean Architecture -->
<div class="row g-4 mb-4">
    @foreach($host->snmpSensors->where('graph_enabled', true) as $sensor)
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-capitalize">{{ $sensor->name ?: $sensor->oid }}</h5>
                    <span class="badge bg-light text-muted border">{{ $sensor->data_type }} @if($sensor->unit) ({{ $sensor->unit }}) @endif</span>
                </div>
                <div class="card-body">
                    <canvas id="chart-sensor-{{ $sensor->id }}" style="max-height: 200px;"></canvas>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Add Sensor Modal -->
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Sensor</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Latency Chart
    const latencyCtx = document.getElementById('latencyChart').getContext('2d');
    const latencyData = @json($host->hostChecks ? $host->hostChecks->take(50)->sortBy('checked_at')->values() : []);
    
    new Chart(latencyCtx, {
        type: 'line',
        data: {
            labels: latencyData.map(d => new Date(d.checked_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})),
            datasets: [{
                label: 'Latency (ms)',
                data: latencyData.map(d => d.latency_ms),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Sensor Metric Charts
    @foreach($host->snmpSensors->where('graph_enabled', true) as $sensor)
        (function() {
            const ctx = document.getElementById('chart-sensor-{{ $sensor->id }}');
            if (!ctx) return;
            
            const data = @json($sensor->sensorMetrics->map(fn($m) => ['t' => $m->recorded_at->toIso8601String(), 'y' => $m->value])->values());
            
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    datasets: [{
                        label: '{{ $sensor->name ?: $sensor->oid }} @if($sensor->unit)({{ $sensor->unit }})@endif',
                        data: data.map(d => ({x: new Date(d.t), y: d.y})),
                        borderColor: '#2196f3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        fill: true,
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' {{ $sensor->unit }}';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            beginAtZero: true
                        },
                        x: { 
                            type: 'time', // Requires chartjs-adapter-date-fns or similar
                            display: false 
                        }
                    }
                }
            });
        })();
    @endforeach
});
</script>
@endpush
@endsection
