@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge-fill me-2 text-primary"></i>{{ $employee->name }}</h4>
        <small class="text-muted">Employee Profile</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        @can('manage-employees')
        <a href="{{ route('admin.employees.edit', $employee->id) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">
    {{-- Profile Card --}}
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 text-center mb-3">
            <div class="card-body py-4">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold mx-auto mb-3"
                     style="width:72px;height:72px;font-size:1.5rem">
                    {{ $employee->initials() }}
                </div>
                <h5 class="fw-bold mb-1">{{ $employee->name }}</h5>
                <div class="text-muted small mb-2">{{ $employee->job_title ?? 'No title' }}</div>
                <span class="badge {{ $employee->statusBadgeClass() }} px-3 py-1">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong><i class="bi bi-info-circle me-1"></i>Details</strong></div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $employee->email ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Branch</dt>
                    <dd class="col-7">{{ $employee->branch?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Department</dt>
                    <dd class="col-7">{{ $employee->department?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Manager</dt>
                    <dd class="col-7">{{ $employee->manager?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Hired</dt>
                    <dd class="col-7">{{ $employee->hired_date?->format('d M Y') ?? '—' }}</dd>
                    @if($employee->terminated_date)
                    <dt class="col-5 text-muted">Terminated</dt>
                    <dd class="col-7">{{ $employee->terminated_date->format('d M Y') }}</dd>
                    @endif
                    @if($employee->azure_id)
                    <dt class="col-5 text-muted">Azure ID</dt>
                    <dd class="col-7"><code class="small">{{ Str::limit($employee->azure_id, 20) }}</code></dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($employee->identityUser)
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-transparent"><strong><i class="bi bi-microsoft me-1"></i>Azure AD</strong></div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Account</dt>
                    <dd class="col-7">
                        <span class="badge {{ $employee->identityUser->account_enabled ? 'bg-success' : 'bg-danger' }}">
                            {{ $employee->identityUser->account_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Licenses</dt>
                    <dd class="col-7">{{ $employee->identityUser->licenses_count ?? 0 }}</dd>
                    <dt class="col-5 text-muted">Groups</dt>
                    <dd class="col-7">{{ $employee->identityUser->groups_count ?? 0 }}</dd>
                </dl>
                <a href="{{ route('admin.identity.user', $employee->identityUser->azure_id) }}" class="btn btn-sm btn-outline-primary mt-2 w-100">
                    <i class="bi bi-box-arrow-up-right me-1"></i>View in Identity
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Assets --}}
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <strong><i class="bi bi-cpu me-1"></i>Assigned Assets</strong>
                @can('manage-employees')
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
                    <i class="bi bi-plus-lg me-1"></i>Assign Asset
                </button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($employee->assetAssignments->isEmpty())
                <div class="text-center py-4 text-muted small"><i class="bi bi-cpu d-block display-5 mb-2"></i>No assets assigned.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Asset</th>
                                <th>Type</th>
                                <th>Condition</th>
                                <th>Assigned</th>
                                <th>Returned</th>
                                <th class="pe-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employee->assetAssignments as $assignment)
                            <tr class="{{ $assignment->returned_date ? 'table-light text-muted' : '' }}">
                                <td class="ps-3 fw-semibold">{{ $assignment->device?->name ?? 'Unknown' }}</td>
                                <td>{{ $assignment->device?->typeLabel() ?? '—' }}</td>
                                <td><span class="badge {{ $assignment->conditionBadgeClass() }}">{{ ucfirst($assignment->condition) }}</span></td>
                                <td>{{ $assignment->assigned_date->format('d M Y') }}</td>
                                <td>
                                    @if($assignment->returned_date)
                                    <span class="text-success">{{ $assignment->returned_date->format('d M Y') }}</span>
                                    @else
                                    <span class="badge bg-primary">Active</span>
                                    @endif
                                </td>
                                <td class="pe-3">
                                    @if(!$assignment->returned_date)
                                    @can('manage-employees')
                                    <button class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#returnModal{{ $assignment->id }}">
                                        Return
                                    </button>
                                    @endcan
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        @if($employee->notes)
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong><i class="bi bi-sticky me-1"></i>Notes</strong></div>
            <div class="card-body small">{{ $employee->notes }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Assign Asset Modal --}}
<div class="modal fade" id="assignAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employees.assets.assign', $employee->id) }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-cpu me-2"></i>Assign Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Select Device <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select form-select-sm" required>
                                <option value="">— Select a device —</option>
                                @foreach($availableDevices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }} ({{ $device->typeLabel() }}) &mdash; {{ $device->serial_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Assigned Date <span class="text-danger">*</span></label>
                            <input type="date" name="assigned_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Condition <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select form-select-sm" required>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Return modals --}}
@foreach($employee->assetAssignments->whereNull('returned_date') as $assignment)
<div class="modal fade" id="returnModal{{ $assignment->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employees.assets.return', [$employee->id, $assignment->id]) }}">
                @csrf @method('PATCH')
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-left me-2"></i>Return Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Returning: <strong>{{ $assignment->device?->name }}</strong></p>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Return Date</label>
                        <input type="date" name="returned_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Condition on Return</label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
