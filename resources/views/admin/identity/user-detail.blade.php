@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.identity.users') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
             style="width:48px;height:48px;font-size:1.1rem;flex-shrink:0">
            {{ $user->initials() }}
        </div>
        <div>
            <h4 class="mb-0 fw-bold">{{ $user->display_name }}</h4>
            <div class="text-muted small">{{ $user->user_principal_name }}</div>
        </div>
        <span class="badge {{ $user->statusBadgeClass() }} ms-1">{{ $user->statusLabel() }}</span>
    </div>
    @can('manage-identity')
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.identity.user.toggle', $user->azure_id) }}" class="d-inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-{{ $user->account_enabled ? 'warning' : 'success' }}"
                    onclick="return confirm('{{ $user->account_enabled ? 'Disable' : 'Enable' }} this user?')">
                <i class="bi bi-{{ $user->account_enabled ? 'lock' : 'unlock' }} me-1"></i>
                {{ $user->account_enabled ? 'Disable' : 'Enable' }}
            </button>
        </form>
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetPwModal">
            <i class="bi bi-key me-1"></i>Reset Password
        </button>
    </div>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">

    {{-- User Info --}}
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>Profile</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless small mb-0">
                    <tr><th class="text-muted" style="width:40%">Title</th><td>{{ $user->job_title ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Department</th><td>{{ $user->department ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Email</th><td class="font-monospace">{{ $user->mail ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Location</th><td>{{ $user->usage_location ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Synced</th><td>{{ $user->updated_at->format('d M Y H:i') }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Licenses --}}
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-patch-check me-2"></i>Licenses ({{ $licenses->count() }})</h6>
                @can('manage-identity')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLicenseModal">
                    <i class="bi bi-plus-lg"></i>
                </button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($licenses->isEmpty())
                <div class="text-center py-3 text-muted small">No licenses assigned.</div>
                @else
                <ul class="list-group list-group-flush small">
                    @foreach($licenses as $lic)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <div class="fw-semibold">{{ $lic->friendlyName() }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ $lic->sku_part_number }}</div>
                        </div>
                        @can('manage-identity')
                        <form method="POST" action="{{ route('admin.identity.user.remove-license', $user->azure_id) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <input type="hidden" name="sku_id" value="{{ $lic->sku_id }}">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Remove this license?')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                        @endcan
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Groups --}}
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-collection me-2"></i>Groups ({{ $groups->count() }})</h6>
                @can('manage-identity')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                    <i class="bi bi-plus-lg"></i>
                </button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($groups->isEmpty())
                <div class="text-center py-3 text-muted small">No group memberships.</div>
                @else
                <ul class="list-group list-group-flush small">
                    @foreach($groups as $grp)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <div class="fw-semibold">{{ $grp->display_name }}</div>
                            <span class="badge {{ $grp->typeBadgeClass() }}" style="font-size:.7rem">{{ $grp->typeLabel() }}</span>
                        </div>
                        @can('manage-identity')
                        <form method="POST" action="{{ route('admin.identity.user.remove-group', $user->azure_id) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <input type="hidden" name="group_id" value="{{ $grp->azure_id }}">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Remove from this group?')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                        @endcan
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ─── Modals ────────────────────────────────────────────────────────── --}}
@can('manage-identity')

{{-- Reset Password Modal --}}
<div class="modal fade" id="resetPwModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.identity.user.reset-password', $user->azure_id) }}">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" required minlength="8"
                               placeholder="Min 8 characters">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="force_change" value="1" id="forceChange" class="form-check-input" checked>
                        <label class="form-check-label" for="forceChange">Require password change on next sign-in</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-key me-1"></i>Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add License Modal --}}
<div class="modal fade" id="addLicenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.identity.user.assign-license', $user->azure_id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-patch-check me-2"></i>Assign License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">License SKU <span class="text-danger">*</span></label>
                    <select name="sku_id" class="form-select" required>
                        <option value="">— Select —</option>
                        @foreach($allLicenses as $lic)
                        @unless(in_array($lic->sku_id, $user->assigned_licenses ?? []))
                        <option value="{{ $lic->sku_id }}">{{ $lic->friendlyName() }} ({{ $lic->available }} available)</option>
                        @endunless
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Group Modal --}}
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.identity.user.add-group', $user->azure_id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-collection me-2"></i>Add to Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Group <span class="text-danger">*</span></label>
                    <select name="group_id" class="form-select" required>
                        <option value="">— Select —</option>
                        @foreach($allGroups as $grp)
                        @unless(in_array($grp->azure_id, $user->member_of ?? []))
                        <option value="{{ $grp->azure_id }}">{{ $grp->display_name }}</option>
                        @endunless
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Add to Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endcan
@endsection
