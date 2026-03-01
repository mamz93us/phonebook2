@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Identity Users</h4>
        <small class="text-muted">
            Synced from Microsoft Entra ID
            @if($lastSync)
            &mdash; last sync {{ $lastSync->created_at->diffForHumans() }}
            @endif
        </small>
    </div>
    @can('manage-identity')
    <form method="POST" action="{{ route('admin.identity.sync') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-arrow-repeat me-1"></i>Sync Now
        </button>
    </form>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Filters --}}
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Name / UPN / Email" value="{{ request('search') }}">
    </div>
    <div class="col-auto">
        <select name="department" class="form-select form-select-sm">
            <option value="">All Departments</option>
            @foreach($departments as $dep)
            <option value="{{ $dep }}" {{ request('department') == $dep ? 'selected' : '' }}>{{ $dep }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="enabled"  {{ request('status') == 'enabled'  ? 'selected' : '' }}>Enabled</option>
            <option value="disabled" {{ request('status') == 'disabled' ? 'selected' : '' }}>Disabled</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
        <a href="{{ route('admin.identity.users') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        @if($users->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people display-4 d-block mb-2"></i>
            No users found.
            @if(!$lastSync) <div class="small mt-1">Run a sync to import users from Entra ID.</div> @endif
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>UPN</th>
                        <th>Department</th>
                        <th class="text-center">Licenses</th>
                        <th class="text-center">Groups</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
                                     style="width:32px;height:32px;font-size:.75rem;flex-shrink:0">
                                    {{ $u->initials() }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $u->display_name }}</div>
                                    <div class="text-muted">{{ $u->job_title ?: '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="font-monospace text-muted">{{ $u->user_principal_name }}</td>
                        <td>{{ $u->department ?: '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $u->licenses_count > 0 ? 'primary' : 'light text-muted border' }}">
                                {{ $u->licenses_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $u->groups_count > 0 ? 'info text-dark' : 'light text-muted border' }}">
                                {{ $u->groups_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $u->statusBadgeClass() }}">{{ $u->statusLabel() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.identity.user', $u->azure_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
