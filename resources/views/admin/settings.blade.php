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
{{-- Microsoft SSO Section                                  --}}
{{-- ─────────────────────────────────────────────────────── --}}
<div class="card mt-4">
    <div class="card-header d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 23 23">
            <rect x="1" y="1" width="10" height="10" fill="#f25022"/>
            <rect x="12" y="1" width="10" height="10" fill="#7fba00"/>
            <rect x="1" y="12" width="10" height="10" fill="#00a4ef"/>
            <rect x="12" y="12" width="10" height="10" fill="#ffb900"/>
        </svg>
        <h5 class="mb-0">Microsoft SSO (Azure AD / Entra ID)</h5>
        @if($settings->sso_enabled)
            <span class="badge bg-success ms-auto">Enabled</span>
        @else
            <span class="badge bg-secondary ms-auto">Disabled</span>
        @endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.sso') }}">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sso_enabled"
                            id="sso_enabled" value="1" {{ $settings->sso_enabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="sso_enabled">
                            Enable Microsoft SSO Login
                        </label>
                    </div>
                    <div class="form-text">Users will see a "Sign in with Microsoft" button on the login page.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tenant ID</label>
                    <input type="text" name="sso_tenant_id" class="form-control font-monospace"
                        value="{{ old('sso_tenant_id', $settings->sso_tenant_id) }}"
                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                    <div class="form-text">Azure Portal → App registrations → Directory (tenant) ID</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client ID (Application ID)</label>
                    <input type="text" name="sso_client_id" class="form-control font-monospace"
                        value="{{ old('sso_client_id', $settings->sso_client_id) }}"
                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                    <div class="form-text">Azure Portal → App registrations → Application (client) ID</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client Secret</label>
                    <input type="password" name="sso_client_secret" class="form-control"
                        placeholder="{{ $settings->sso_client_secret ? '••••••••  (leave blank to keep current)' : 'Paste secret value here' }}">
                    <div class="form-text">Azure Portal → App registrations → Certificates &amp; secrets</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Default Role for New SSO Users</label>
                    <select name="sso_default_role" class="form-select">
                        <option value="viewer"      {{ ($settings->sso_default_role ?? 'viewer') === 'viewer'      ? 'selected' : '' }}>Viewer (read-only)</option>
                        <option value="admin"       {{ ($settings->sso_default_role ?? '') === 'admin'       ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ ($settings->sso_default_role ?? '') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                    <div class="form-text">Role assigned when a new Microsoft user logs in for the first time.</div>
                </div>
            </div>

            <div class="alert alert-info py-2 small mb-3">
                <strong>Azure Redirect URI to register:</strong>
                <code class="ms-1">{{ url('/auth/microsoft/callback') }}</code>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save SSO Settings
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
                                            <label class="form-label">
                                                Wave / SIP Cloud Domain
                                                <span class="badge bg-info text-dark ms-1" style="font-size:10px">For QR Code</span>
                                            </label>
                                            <input type="text" name="cloud_domain" class="form-control font-monospace"
                                                value="{{ $ucm->cloud_domain }}"
                                                placeholder="e.g. msc1abc.gdms.cloud">
                                            <div class="form-text">
                                                GDMS cloud relay hostname used in Wave QR codes.
                                                Leave blank to use the hostname from the URL above.
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

{{-- ─────────────────────────────────────────────────────── --}}
{{-- Meraki Network Section                                 --}}
{{-- ─────────────────────────────────────────────────────── --}}
<div class="card mt-4" id="meraki">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3-fill text-primary fs-5"></i>
        <h5 class="mb-0">Meraki Network Integration</h5>
        @if($settings->meraki_enabled)
            <span class="badge bg-success ms-auto">Enabled</span>
        @else
            <span class="badge bg-secondary ms-auto">Disabled</span>
        @endif
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Read-only observability.</strong>
            This integration fetches switch status, port states, and client data from the Meraki API.
            No write operations are performed on your network.
        </div>
        <form method="POST" action="{{ route('admin.settings.meraki') }}">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="meraki_enabled"
                            id="meraki_enabled" value="1" {{ $settings->meraki_enabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="meraki_enabled">
                            Enable Meraki Network Monitoring
                        </label>
                    </div>
                    <div class="form-text">Enable to show the Network section in the navigation.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">API Key <span class="text-danger">*</span></label>
                    <input type="password" name="meraki_api_key" class="form-control font-monospace"
                        placeholder="{{ $settings->meraki_api_key ? '••••••••  (leave blank to keep current)' : 'Paste Meraki API key here' }}">
                    <div class="form-text">
                        Meraki Dashboard → Account → API access → Generate API key.
                        Stored encrypted.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Organisation ID <span class="text-danger">*</span></label>
                    <input type="text" name="meraki_org_id" class="form-control font-monospace"
                        value="{{ old('meraki_org_id', $settings->meraki_org_id) }}"
                        placeholder="e.g. 123456">
                    <div class="form-text">
                        Meraki Dashboard → Organisation → Settings → Organisation ID.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Polling Interval (minutes)</label>
                    <input type="number" name="meraki_polling_interval" class="form-control"
                        value="{{ old('meraki_polling_interval', $settings->meraki_polling_interval ?? 15) }}"
                        min="5" max="1440">
                    <div class="form-text">How often the background sync job runs (min: 5).</div>
                </div>

                <div class="col-md-8 d-flex align-items-end">
                    <div class="w-100">
                        <label class="form-label fw-semibold">Connection Test</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="testMerakiBtn"
                                onclick="testMerakiConnection()">
                                <i class="bi bi-plug me-1"></i>Test Connection
                            </button>
                            <span class="input-group-text flex-grow-1" id="merakiTestResult" style="min-width:200px">
                                <span class="text-muted small">Click to test current credentials</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Meraki Settings
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────── --}}
{{-- Microsoft Graph / Identity Section                     --}}
{{-- ─────────────────────────────────────────────────────── --}}
@can('manage-identity-settings')
<div class="card mt-4" id="graph">
    <div class="card-header d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 23 23">
            <rect x="1" y="1" width="10" height="10" fill="#f25022"/>
            <rect x="12" y="1" width="10" height="10" fill="#7fba00"/>
            <rect x="1" y="12" width="10" height="10" fill="#00a4ef"/>
            <rect x="12" y="12" width="10" height="10" fill="#ffb900"/>
        </svg>
        <h5 class="mb-0">Microsoft Graph API (Identity Sync)</h5>
        @if($settings->identity_sync_enabled)
            <span class="badge bg-success ms-auto">Enabled</span>
        @else
            <span class="badge bg-secondary ms-auto">Disabled</span>
        @endif
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Connects to Microsoft Graph API to sync Entra ID users, licenses, and groups into the Identity module.
            Requires an App Registration with <code>User.Read.All</code>, <code>Group.Read.All</code>,
            and <code>Directory.Read.All</code> application permissions.
        </div>
        <form method="POST" action="{{ route('admin.settings.graph') }}">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="identity_sync_enabled"
                            id="identity_sync_enabled" value="1" {{ $settings->identity_sync_enabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="identity_sync_enabled">
                            Enable Automatic Identity Sync
                        </label>
                    </div>
                    <div class="form-text">Runs a background sync job on the configured interval.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tenant ID <span class="text-danger">*</span></label>
                    <input type="text" name="graph_tenant_id" class="form-control font-monospace"
                        value="{{ old('graph_tenant_id', $settings->graph_tenant_id) }}"
                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                    <div class="form-text">Azure Portal → App registrations → Directory (tenant) ID</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client ID (Application ID) <span class="text-danger">*</span></label>
                    <input type="text" name="graph_client_id" class="form-control font-monospace"
                        value="{{ old('graph_client_id', $settings->graph_client_id) }}"
                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                    <div class="form-text">Azure Portal → App registrations → Application (client) ID</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client Secret <span class="text-danger">*</span></label>
                    <input type="password" name="graph_client_secret" class="form-control"
                        placeholder="{{ $settings->graph_client_secret ? '••••••••  (leave blank to keep current)' : 'Paste secret value here' }}">
                    <div class="form-text">Azure Portal → App registrations → Certificates &amp; secrets</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sync Interval (minutes)</label>
                    <input type="number" name="identity_sync_interval" class="form-control"
                        value="{{ old('identity_sync_interval', $settings->identity_sync_interval ?? 60) }}"
                        min="15" max="1440">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Default Password</label>
                    <input type="text" name="graph_default_password" class="form-control"
                        value="{{ old('graph_default_password', $settings->graph_default_password) }}"
                        placeholder="TempPass@123">
                    <div class="form-text">Used when resetting passwords.</div>
                </div>

                <div class="col-12 d-flex align-items-end">
                    <div>
                        <label class="form-label fw-semibold">Connection Test</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="testGraphBtn"
                                onclick="testGraphConnection()">
                                <i class="bi bi-plug me-1"></i>Test Connection
                            </button>
                            <span class="input-group-text flex-grow-1" id="graphTestResult" style="min-width:220px">
                                <span class="text-muted small">Click to test credentials</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Graph Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

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
                        <label class="form-label">
                            Wave / SIP Cloud Domain
                            <span class="badge bg-info text-dark ms-1" style="font-size:10px">For QR Code</span>
                        </label>
                        <input type="text" name="cloud_domain" class="form-control font-monospace"
                            placeholder="e.g. msc1abc.gdms.cloud">
                        <div class="form-text">
                            GDMS cloud relay hostname used in Wave QR codes.
                            Leave blank to use the hostname from the URL above.
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

@push('scripts')
<script>
function testGraphConnection() {
    const btn       = document.getElementById('testGraphBtn');
    const result    = document.getElementById('graphTestResult');
    const tenantId  = document.querySelector('[name="graph_tenant_id"]').value;
    const clientId  = document.querySelector('[name="graph_client_id"]').value;
    const secret    = document.querySelector('[name="graph_client_secret"]').value;

    if (!tenantId || !clientId) {
        result.innerHTML = '<span class="text-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Enter Tenant ID and Client ID</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing…';
    result.innerHTML = '<span class="text-muted small">Connecting…</span>';

    fetch('{{ route('admin.settings.test-graph') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ tenant_id: tenantId, client_id: clientId, client_secret: secret })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            result.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</span>';
        } else {
            result.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle-fill me-1"></i>' + data.message + '</span>';
        }
    })
    .catch(() => {
        result.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle-fill me-1"></i>Request failed</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug me-1"></i>Test Connection';
    });
}

function testMerakiConnection() {
    const btn    = document.getElementById('testMerakiBtn');
    const result = document.getElementById('merakiTestResult');
    const apiKey = document.querySelector('[name="meraki_api_key"]').value;
    const orgId  = document.querySelector('[name="meraki_org_id"]').value;

    if (!orgId) {
        result.innerHTML = '<span class="text-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Please enter an Organisation ID</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing…';
    result.innerHTML = '<span class="text-muted small">Connecting…</span>';

    fetch('{{ route('admin.settings.test-meraki') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ api_key: apiKey || '{{-- use saved --}}', org_id: orgId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            result.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</span>';
        } else {
            result.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle-fill me-1"></i>' + data.message + '</span>';
        }
    })
    .catch(() => {
        result.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle-fill me-1"></i>Request failed</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug me-1"></i>Test Connection';
    });
}
</script>
@endpush
