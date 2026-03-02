@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2 text-primary"></i>NOC Dashboard</h4>
        <small class="text-muted">Unified IT operations overview &bull; Updated {{ now()->format('H:i') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.noc.events') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-exclamation-triangle me-1"></i>All Events
        </a>
        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-repeat me-1"></i>Refresh
        </button>
    </div>
</div>

{{-- Module Summary Cards --}}
<div class="row g-3 mb-4">
    {{-- Identity --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                        <i class="bi bi-people-fill text-primary"></i>
                    </div>
                    <span class="fw-semibold small">Identity</span>
                </div>
                <div class="display-6 fw-bold text-primary">{{ number_format($totalUsers) }}</div>
                <div class="small text-muted">Total Users</div>
                <div class="mt-2 d-flex justify-content-between small text-muted mb-1">
                    <span>Licensed</span><span>{{ $licensedPercent }}%</span>
                </div>
                <div class="progress" style="height:5px">
                    <div class="progress-bar bg-success" style="width:{{ $licensedPercent }}%"></div>
                </div>
                @if($disabledUsers > 0)
                <div class="mt-1 small text-warning"><i class="bi bi-exclamation-triangle me-1"></i>{{ $disabledUsers }} disabled</div>
                @endif
            </div>
            <div class="card-footer bg-transparent py-2">
                <a href="{{ route('admin.identity.users') }}" class="btn btn-sm btn-outline-primary w-100">View Users</a>
            </div>
        </div>
    </div>

    {{-- Network --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                        <i class="bi bi-diagram-3-fill text-info"></i>
                    </div>
                    <span class="fw-semibold small">Network</span>
                </div>
                <div class="display-6 fw-bold text-info">{{ $totalSwitches }}</div>
                <div class="small text-muted">Total Switches</div>
                <div class="mt-2 d-flex justify-content-between small text-muted mb-1">
                    <span>Online</span><span>{{ $onlinePercent }}%</span>
                </div>
                <div class="progress" style="height:5px">
                    <div class="progress-bar {{ $onlinePercent >= 90 ? 'bg-success' : ($onlinePercent >= 70 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ $onlinePercent }}%"></div>
                </div>
                @if($onlineSwitches < $totalSwitches)
                <div class="mt-1 small text-danger"><i class="bi bi-exclamation-circle me-1"></i>{{ $totalSwitches - $onlineSwitches }} offline</div>
                @endif
            </div>
            <div class="card-footer bg-transparent py-2">
                <a href="{{ route('admin.network.overview') }}" class="btn btn-sm btn-outline-info w-100">View Network</a>
            </div>
        </div>
    </div>

    {{-- Assets --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                        <i class="bi bi-cpu-fill text-success"></i>
                    </div>
                    <span class="fw-semibold small">Assets</span>
                </div>
                <div class="display-6 fw-bold text-success">{{ number_format($totalDevices) }}</div>
                <div class="small text-muted">Total Devices</div>
                <div class="mt-2">
                    @if($missingCreds > 0)
                    <div class="small text-warning"><i class="bi bi-key me-1"></i>{{ $missingCreds }} missing credentials</div>
                    @else
                    <div class="small text-success"><i class="bi bi-check-circle me-1"></i>All devices have credentials</div>
                    @endif
                    @if($printersOverdue > 0)
                    <div class="small text-danger mt-1"><i class="bi bi-printer me-1"></i>{{ $printersOverdue }} printers overdue service</div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-transparent py-2">
                <a href="{{ route('admin.devices.index') }}" class="btn btn-sm btn-outline-success w-100">View Devices</a>
            </div>
        </div>
    </div>

    {{-- Workflows --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                        <i class="bi bi-person-badge-fill text-warning"></i>
                    </div>
                    <span class="fw-semibold small">Workflows</span>
                </div>
                @php $pendingWf = \App\Models\WorkflowRequest::where('status','pending')->count(); @endphp
                <div class="display-6 fw-bold text-warning">{{ $pendingWf }}</div>
                <div class="small text-muted">Pending Approvals</div>
                @php $totalWf = \App\Models\WorkflowRequest::count(); @endphp
                <div class="mt-2 small text-muted">{{ $totalWf }} total requests</div>
            </div>
            <div class="card-footer bg-transparent py-2">
                <a href="{{ route('admin.workflows.pending') }}" class="btn btn-sm btn-outline-warning w-100">Pending Approvals</a>
            </div>
        </div>
    </div>
</div>

{{-- Open NOC Events --}}
@if($openEvents->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
        <strong><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Open Alerts <span class="badge bg-danger ms-1">{{ $openEvents->count() }}</span></strong>
        <a href="{{ route('admin.noc.events') }}" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <tbody>
                    @foreach($openEvents as $event)
                    <tr>
                        <td class="ps-3" style="width:8px;padding-right:0">
                            <div style="width:4px;height:100%;background:{{ $event->severity === 'critical' ? '#dc3545' : ($event->severity === 'warning' ? '#ffc107' : '#0dcaf0') }};border-radius:2px">&nbsp;</div>
                        </td>
                        <td style="width:100px"><span class="badge {{ $event->severityBadgeClass() }}">{{ ucfirst($event->severity) }}</span></td>
                        <td><i class="{{ $event->moduleIcon() }} me-1 text-muted"></i>{{ $event->title }}</td>
                        <td class="text-muted">{{ Str::limit($event->message, 80) }}</td>
                        <td class="text-muted text-nowrap">{{ $event->last_seen->diffForHumans() }}</td>
                        <td class="pe-3">
                            @can('manage-noc')
                            <form method="POST" action="{{ route('admin.noc.events.acknowledge', $event->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-secondary">Ack</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Branch Health Table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent">
        <strong><i class="bi bi-building me-1"></i>Branch Health Scores</strong>
    </div>
    <div class="card-body p-0">
        @if($branches->isEmpty())
        <div class="text-center py-4 text-muted small">No branches found.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Branch</th>
                        <th>Overall</th>
                        <th>Identity</th>
                        <th>Network</th>
                        <th>Assets</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $branch)
                    @php $h = $branch->health ?? ['total'=>0,'identity'=>0,'voice'=>0,'network'=>0,'asset'=>0]; @endphp
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $branch->name }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;min-width:80px">
                                    <div class="progress-bar bg-{{ \App\Services\HealthScoringService::healthColorStatic($h['total']) }}" style="width:{{ $h['total'] }}%"></div>
                                </div>
                                <span class="fw-bold text-{{ \App\Services\HealthScoringService::healthColorStatic($h['total']) }}">{{ $h['total'] }}%</span>
                            </div>
                        </td>
                        <td><span class="badge bg-{{ \App\Services\HealthScoringService::healthColorStatic($h['identity']) }}">{{ $h['identity'] }}%</span></td>
                        <td><span class="badge bg-{{ \App\Services\HealthScoringService::healthColorStatic($h['network']) }}">{{ $h['network'] }}%</span></td>
                        <td><span class="badge bg-{{ \App\Services\HealthScoringService::healthColorStatic($h['asset']) }}">{{ $h['asset'] }}%</span></td>
                        <td class="pe-3">
                            <a href="{{ route('admin.noc.branch', $branch->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-zoom-in"></i>
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
