@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle-fill me-2 text-primary"></i>New Workflow Request</h4>
        <small class="text-muted">Submit a request for approval and processing</small>
    </div>
    <a href="{{ route('admin.workflows.my-requests') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.workflows.store') }}">
                    @csrf

                    {{-- Type selector --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Request Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            @foreach($types as $val => $label)
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_{{ $val }}" value="{{ $val }}" {{ ($type ?? old('type')) === $val ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="type_{{ $val }}">{{ $label }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Request Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Brief description of this request" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Additional context or details...">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">— Select Branch —</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Dynamic fields for create_user --}}
                    <div id="create_user_fields" class="mt-4 d-none">
                        <hr><h6 class="text-muted fw-semibold">New User Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">First Name</label>
                                <input type="text" name="first_name" class="form-control form-control-sm" value="{{ old('first_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Last Name</label>
                                <input type="text" name="last_name" class="form-control form-control-sm" value="{{ old('last_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">User Principal Name (UPN)</label>
                                <input type="email" name="user_principal_name" class="form-control form-control-sm" placeholder="user@domain.com" value="{{ old('user_principal_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Job Title</label>
                                <input type="text" name="job_title" class="form-control form-control-sm" value="{{ old('job_title') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Department</label>
                                <input type="text" name="department" class="form-control form-control-sm" value="{{ old('department') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Usage Location</label>
                                <input type="text" name="usage_location" class="form-control form-control-sm" placeholder="SA, US, GB..." value="{{ old('usage_location', 'SA') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">License SKU ID</label>
                                <input type="text" name="license_sku" class="form-control form-control-sm" placeholder="Optional" value="{{ old('license_sku') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Generic other details field --}}
                    <div id="other_fields" class="mt-4 d-none">
                        <hr><h6 class="text-muted fw-semibold">Additional Information</h6>
                        <textarea name="details" class="form-control form-control-sm" rows="4" placeholder="Provide any relevant details...">{{ old('details') }}</textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Request
                        </button>
                        <a href="{{ route('admin.workflows.my-requests') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar info --}}
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong><i class="bi bi-info-circle me-1"></i>Approval Process</strong></div>
            <div class="card-body small">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><span class="badge bg-success me-1">Create User</span> HR &rarr; IT Manager</li>
                    <li class="mb-2"><span class="badge bg-danger me-1">Delete User</span> IT Manager &rarr; Super Admin</li>
                    <li class="mb-2"><span class="badge bg-info text-dark me-1">License</span> IT Manager</li>
                    <li class="mb-2"><span class="badge bg-secondary me-1">Asset</span> Manager</li>
                    <li><span class="badge bg-primary me-1">Extension</span> IT Manager</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('create_user_fields').classList.add('d-none');
        document.getElementById('other_fields').classList.add('d-none');
        if (this.value === 'create_user') {
            document.getElementById('create_user_fields').classList.remove('d-none');
        } else if (this.value === 'other') {
            document.getElementById('other_fields').classList.remove('d-none');
        }
    });
});
// Trigger on page load if type pre-selected
const checked = document.querySelector('input[name="type"]:checked');
if (checked) checked.dispatchEvent(new Event('change'));
</script>
@endpush
@endsection
