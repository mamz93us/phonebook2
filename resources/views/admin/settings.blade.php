@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Settings</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf

            <!-- Company Name -->
            <div class="mb-4">
                <label for="company_name" class="form-label">Company Name</label>
                <input 
                    type="text" 
                    name="company_name" 
                    id="company_name"
                    value="{{ old('company_name', $settings->company_name) }}"
                    class="form-control @error('company_name') is-invalid @enderror"
                    required
                >
                @error('company_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Company Logo -->
            <div class="mb-4">
                <label class="form-label">Company Logo</label>

                @if($settings->company_logo)
                    <div class="mb-3">
                        <img 
                            src="{{ asset('storage/' . $settings->company_logo) }}" 
                            alt="Company Logo" 
                            class="img-thumbnail"
                            style="max-width: 300px;"
                        >
                        <form method="POST" action="{{ route('admin.settings.delete-logo') }}" class="mt-2 d-inline">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete the logo?')"
                            >
                                Delete Logo
                            </button>
                        </form>
                    </div>
                @endif

                <input 
                    type="file" 
                    name="company_logo" 
                    id="company_logo"
                    accept="image/*"
                    class="form-control @error('company_logo') is-invalid @enderror"
                >
                <div class="form-text">Recommended: PNG or JPG, max 2MB</div>
                @error('company_logo')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────── --}}
{{-- UCM Servers Section                                    --}}
{{-- ─────────────────────────────────────────────────────── --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>UCM / IPPBX Servers</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUcmModal">
            <i class="bi bi-plus-circle me-1"></i> Add UCM Server
        </button>
    </div>
    <div class="card-body p-0">
        @if($ucmServers->isEmpty())
            <div class="text-center text-muted py-4">No UCM servers configured yet.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>URL</th>
                        <th>API Username</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ucmServers as $ucm)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $ucm->name }}</strong></td>
                        <td><code>{{ $ucm->url }}</code></td>
                        <td>{{ $ucm->api_username }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.ucm-servers.toggle', $ucm) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $ucm->is_active ? 'btn-success' : 'btn-secondary' }}">
                                    {{ $ucm->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#editUcmModal{{ $ucm->id }}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('admin.ucm-servers.destroy', $ucm) }}" class="d-inline"
                                onsubmit="return confirm('Delete {{ $ucm->name }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>

                    {{-- Edit Modal for each UCM --}}
                    <div class="modal fade" id="editUcmModal{{ $ucm->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.ucm-servers.update', $ucm) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit UCM Server</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name / Label <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $ucm->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">URL <span class="text-danger">*</span></label>
                                            <input type="text" name="url" class="form-control" value="{{ $ucm->url }}" required
                                                placeholder="https://msc1abc.gdms.cloud  or  https://192.168.1.100:8089">
                                            <div class="form-text">
                                                Cloud (GDMS): no port needed &nbsp;|&nbsp; Local UCM: add <code>:8089</code>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">API Username <span class="text-danger">*</span></label>
                                            <input type="text" name="api_username" class="form-control" value="{{ $ucm->api_username }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">API Password</label>
                                            <input type="password" name="api_password" class="form-control"
                                                placeholder="Leave blank to keep current password">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Add UCM Modal --}}
<div class="modal fade" id="addUcmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.ucm-servers.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-hdd-network me-2"></i>Add UCM Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name / Label <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="e.g. Main Office UCM">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL <span class="text-danger">*</span></label>
                        <input type="text" name="url" class="form-control" required
                            placeholder="https://msc1abc.gdms.cloud  or  https://192.168.1.100:8089">
                        <div class="form-text">
                            Cloud (GDMS): no port needed &nbsp;|&nbsp; Local UCM: add <code>:8089</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Username <span class="text-danger">*</span></label>
                        <input type="text" name="api_username" class="form-control" required
                            placeholder="admin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Password <span class="text-danger">*</span></label>
                        <input type="password" name="api_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Server</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
