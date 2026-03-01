@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-collection-fill me-2 text-primary"></i>Groups</h4>
        <small class="text-muted">
            Microsoft Entra ID groups
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

<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search groups..." value="{{ request('search') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
        <a href="{{ route('admin.identity.groups') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        @if($groups->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-collection display-4 d-block mb-2"></i>
            No groups found. Run a sync to import from Entra ID.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Display Name</th>
                        <th>Type</th>
                        <th class="text-center">Members</th>
                        <th class="text-center">Mail</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $g)
                    <tr>
                        <td class="fw-semibold">{{ $g->display_name }}</td>
                        <td><span class="badge {{ $g->typeBadgeClass() }}">{{ $g->typeLabel() }}</span></td>
                        <td class="text-center">
                            <span class="badge bg-{{ $g->members_count > 0 ? 'primary' : 'light text-muted border' }}">
                                {{ $g->members_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($g->mail_enabled)
                            <span class="badge bg-success"><i class="bi bi-envelope-check me-1"></i>Yes</span>
                            @else
                            <span class="badge bg-light text-muted border">No</span>
                            @endif
                        </td>
                        <td class="text-muted">
                            @if($g->description)
                            <span title="{{ $g->description }}">{{ Str::limit($g->description, 60) }}</span>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $groups->links() }}</div>
        @endif
    </div>
</div>
@endsection
