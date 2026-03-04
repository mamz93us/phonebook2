@extends('layouts.admin')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-end">
    <div>
        <a href="{{ route('admin.network.monitoring.index') }}" class="btn btn-link link-secondary ps-0">
            <i class="bi bi-arrow-left me-1"></i> Back to Monitoring
        </a>
        <h2 class="h3 mt-2 mb-0">{{ $host->name }}</h2>
        <div class="d-flex align-items-center mt-2">
            <span class="badge bg-{{ $host->status == 'up' ? 'success' : 'danger' }} me-2">
                {{ strtoupper($host->status) }}
            </span>
            <code class="text-muted pe-3 border-end me-3">{{ $host->ip }}</code>
            <span class="text-muted small">Type: <span class="fw-bold text-dark">{{ strtoupper($host->type) }}</span></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh Data
        </button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSensorModal">
            <i class="bi bi-plus-lg"></i> Add Custom Sensor (OID)
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
                    <span class="h4 mb-0 fw-bold">{{ round($host->networkChecks->avg('latency'), 2) }}ms</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Packet Loss</span>
                    <span class="h4 mb-0 fw-bold text-{{ $host->networkChecks->avg('packet_loss') > 5 ? 'danger' : 'success' }}">
                        {{ round($host->networkChecks->avg('packet_loss'), 1) }}%
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
                                <span class="fw-bold d-block">{{ $sensor->description ?: 'Unnamed Sensor' }}</span>
                                <span class="text-muted small">{{ $sensor->oid }}</span>
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

<!-- Dynamic Metric Graphs -->
<div class="row g-4">
    @php
        $uniqueMetrics = $host->metrics->pluck('metric_name')->unique();
    @endphp
    
    @foreach($uniqueMetrics as $metric)
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 text-capitalize">{{ str_replace('_', ' ', $metric) }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="chart-{{ Str::slug($metric) }}" style="max-height: 200px;"></canvas>
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
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Add SNMP Sensor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sensor OID</label>
                        <input type="text" name="oid" class="form-control" placeholder=".1.3.6.1.4.1..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Label</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. CPU Temperature">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="graph_enabled" value="1" id="graphEnabled" checked>
                        <label class="form-check-label" for="graphEnabled">Show in graph dashboard</label>
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
    const latencyData = @json($host->networkChecks->take(50)->sortBy('checked_at')->values());
    
    new Chart(latencyCtx, {
        type: 'line',
        data: {
            labels: latencyData.map(d => new Date(d.checked_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})),
            datasets: [{
                label: 'Latency (ms)',
                data: latencyData.map(d => d.latency),
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

    // 2. Dynamic Metric Charts
    @foreach($uniqueMetrics as $metric)
        (function() {
            const ctx = document.getElementById('chart-{{ Str::slug($metric) }}').getContext('2d');
            const data = @json($host->metrics->where('metric_name', $metric)->take(30)->sortBy('recorded_at')->values());
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => new Date(d.recorded_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})),
                    datasets: [{
                        label: '{{ $metric }}',
                        data: data.map(d => d.value),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.05)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: 'rgba(0,0,0,0.03)' } },
                        x: { display: false }
                    }
                }
            });
        })();
    @endforeach
});
</script>
@endpush
@endsection
