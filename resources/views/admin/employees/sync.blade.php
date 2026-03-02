@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-down-fill me-2 text-primary"></i>Sync Employees from Azure</h4>
        <small class="text-muted">Import users from Azure AD that have not yet been linked to an employee record</small>
    </div>
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Employees
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if($azureUsers->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    All Azure AD users are already linked to employee records, or no users match the domain filters.
</div>
@else
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-people me-1"></i>{{ $azureUsers->count() }} unlinked Azure user(s)</strong>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll(true)">Select All</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll(false)">Deselect All</button>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="POST" action="{{ route('admin.employees.sync.do') }}" id="syncForm">
            @csrf

            {{-- Optional defaults for imported employees --}}
            <div class="p-3 bg-light border-bottom">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Assign to Branch (optional)</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">— No Branch —</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Assign to Department (optional)</label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="">— No Department —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm" id="importBtn" disabled>
                            <i class="bi bi-cloud-arrow-down me-1"></i>Import Selected
                        </button>
                    </div>
                </div>
            </div>

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"><input type="checkbox" class="form-check-input" id="checkAll" onchange="toggleAll(this)"></th>
                        <th>Name</th>
                        <th>UPN / Email</th>
                        <th>Department</th>
                        <th>Job Title</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($azureUsers as $azUser)
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input row-check" name="azure_ids[]" value="{{ $azUser->azure_id }}"
                               onchange="updateImportBtn()">
                    </td>
                    <td class="fw-semibold">{{ $azUser->display_name }}</td>
                    <td class="small text-muted">{{ $azUser->user_principal_name }}</td>
                    <td class="small">{{ $azUser->department ?? '—' }}</td>
                    <td class="small">{{ $azUser->job_title ?? '—' }}</td>
                    <td class="text-center">
                        @if($azUser->account_enabled)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Disabled</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function selectAll(state) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = state);
    updateImportBtn();
}
function toggleAll(masterCb) {
    selectAll(masterCb.checked);
}
function updateImportBtn() {
    const anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
    document.getElementById('importBtn').disabled = !anyChecked;
    document.getElementById('checkAll').checked = anyChecked &&
        document.querySelectorAll('.row-check:checked').length === document.querySelectorAll('.row-check').length;
}
</script>
@endpush
@endsection
