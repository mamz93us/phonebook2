@extends('layouts.admin')

@section('content')

{{-- ─── Page Header ──────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-telephone-fill me-2"></i>Extensions</h1>

    @if($selectedUcm)
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExtensionModal">
            <i class="bi bi-plus-circle me-1"></i> Add Extension
        </button>
    @endif
</div>

{{-- ─── Flash Messages ───────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error') || $error)
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') ?? $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ─── UCM Server Selector ──────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body">
        @if($ucmServers->isEmpty())
            <div class="text-center py-3">
                <i class="bi bi-hdd-network fs-2 text-muted"></i>
                <p class="mt-2 text-muted">No UCM servers configured yet.</p>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-gear me-1"></i> Go to Settings to add a UCM server
                </a>
            </div>
        @else
            <form method="GET" action="{{ route('admin.extensions.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-hdd-network me-1"></i>Select UCM Server
                    </label>
                    <select name="ucm_id" class="form-select" onchange="this.form.submit()">
                        <option value="">— Choose a server —</option>
                        @foreach($ucmServers as $ucm)
                            <option value="{{ $ucm->id }}"
                                {{ optional($selectedUcm)->id == $ucm->id ? 'selected' : '' }}>
                                {{ $ucm->name }} — {{ $ucm->url }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selectedUcm)
                <div class="col-auto">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>
                        Connected: {{ $selectedUcm->name }}
                    </span>
                </div>
                @endif
            </form>
        @endif
    </div>
</div>

{{-- ─── Extensions Table ─────────────────────────────────────── --}}
@if($selectedUcm && !$error)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list-ul me-2"></i>
            Extensions on <strong>{{ $selectedUcm->name }}</strong>
        </span>
        <span class="badge bg-secondary">{{ count($extensions) }} total</span>
    </div>

    @if(empty($extensions))
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-telephone-x fs-1"></i>
            <p class="mt-3">No extensions found on this UCM server.</p>
        </div>
    @else
    <div class="card-body p-0">
        {{-- Search box --}}
        <div class="p-3 border-bottom">
            <input type="text" id="extSearch" class="form-control" placeholder="🔍  Search by extension number or name...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="extensionsTable">
                <thead class="table-light">
                    <tr>
                        <th>Extension</th>
                        <th>Full Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($extensions as $ext)
                    @php
                        $statusClass = match($ext['status'] ?? '') {
                            'Idle'        => 'success',
                            'InUse'       => 'primary',
                            'Busy'        => 'warning',
                            'Ringing'     => 'info',
                            'Unavailable' => 'secondary',
                            default       => 'light',
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $ext['extension'] ?? '-' }}</strong></td>
                        <td>{{ $ext['fullname'] ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $ext['account_type'] ?? '-' }}</span></td>
                        <td>
                            <span class="badge bg-{{ $statusClass }}">
                                {{ $ext['status'] ?? 'Unknown' }}
                            </span>
                        </td>
                        <td>
                            @if(!empty($ext['addr']) && $ext['addr'] !== '-')
                                <code>{{ $ext['addr'] }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            {{-- Wave credentials button --}}
                            <button class="btn btn-sm btn-outline-info me-1"
                                title="Wave / SIP Credentials"
                                onclick="loadWave('{{ $ext['extension'] }}', {{ $selectedUcm->id }})">
                                <i class="bi bi-qr-code"></i>
                            </button>
                            {{-- Edit button --}}
                            <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $ext['extension'] }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            {{-- Delete button --}}
                            <form method="POST"
                                action="{{ route('admin.extensions.destroy', $ext['extension']) }}"
                                class="d-inline"
                                onsubmit="return confirm('Delete extension {{ $ext['extension'] }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="ucm_id" value="{{ $selectedUcm->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    {{-- Edit Modal for this extension --}}
                    <div class="modal fade" id="editModal{{ $ext['extension'] }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.extensions.update', $ext['extension']) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="ucm_id" value="{{ $selectedUcm->id }}">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-pencil-square me-2"></i>Edit Extension {{ $ext['extension'] }}
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Extension</label>
                                                <input type="text" class="form-control bg-light" value="{{ $ext['extension'] }}" disabled>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label fw-semibold">Full Name</label>
                                                <input type="text" name="fullname" class="form-control"
                                                    value="{{ $ext['fullname'] ?? '' }}" placeholder="e.g. John Doe">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Email</label>
                                                <input type="email" name="email" class="form-control"
                                                    value="{{ $ext['email'] ?? '' }}" placeholder="user@company.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Permission <span class="text-danger">*</span></label>
                                                <select name="permission" class="form-select" required>
                                                    @foreach(['internal','local','national','international'] as $perm)
                                                        <option value="{{ $perm }}" {{ ($ext['permission'] ?? 'internal') === $perm ? 'selected' : '' }}>
                                                            {{ ucfirst($perm) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Max Contacts</label>
                                                <input type="number" name="max_contacts" class="form-control"
                                                    value="{{ $ext['max_contacts'] ?? 3 }}" min="1" max="10">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">New SIP Password
                                                    <small class="text-muted fw-normal">(leave blank to keep current)</small>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" name="secret"
                                                        id="edit_secret_{{ $ext['extension'] }}"
                                                        class="form-control font-monospace"
                                                        placeholder="Leave blank to keep unchanged">
                                                    <button type="button" class="btn btn-outline-secondary" title="Generate"
                                                        onclick="generatePassword('edit_secret_{{ $ext['extension'] }}')">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" title="Copy"
                                                        onclick="copyToClipboard('edit_secret_{{ $ext['extension'] }}')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold d-block mb-2">Features</label>
                                                <div class="d-flex flex-wrap gap-4">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="voicemail_enable"
                                                            id="edit_vm_{{ $ext['extension'] }}"
                                                            value="yes"
                                                            {{ ($ext['hasvoicemail'] ?? 'yes') === 'yes' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="edit_vm_{{ $ext['extension'] }}">
                                                            <i class="bi bi-voicemail me-1"></i>Voicemail
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="call_waiting"
                                                            id="edit_cw_{{ $ext['extension'] }}"
                                                            value="yes"
                                                            {{ ($ext['call_waiting'] ?? 'yes') === 'yes' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="edit_cw_{{ $ext['extension'] }}">
                                                            <i class="bi bi-telephone-plus me-1"></i>Call Waiting
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="dnd"
                                                            id="edit_dnd_{{ $ext['extension'] }}"
                                                            value="yes"
                                                            {{ ($ext['dnd'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="edit_dnd_{{ $ext['extension'] }}">
                                                            <i class="bi bi-slash-circle me-1"></i>Do Not Disturb
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ─── Add Extension Modal ──────────────────────────────────── --}}
@if($selectedUcm)
<div class="modal fade" id="addExtensionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.extensions.store') }}">
                @csrf
                <input type="hidden" name="ucm_id" value="{{ $selectedUcm->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Add New Extension
                        <small class="opacity-75 fs-6">— {{ $selectedUcm->name }}</small>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">

                        {{-- Extension Number & Full Name --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Extension Number <span class="text-danger">*</span></label>
                            <input type="text" name="extension" id="add_extension" class="form-control" required
                                placeholder="e.g. 1001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="fullname" class="form-control" placeholder="e.g. John Doe">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="user@company.com">
                        </div>

                        {{-- SIP Password --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SIP Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="secret" id="add_secret" class="form-control font-monospace" required
                                    placeholder="Min 6 characters">
                                <button type="button" class="btn btn-outline-secondary" title="Generate"
                                    onclick="generatePassword('add_secret')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" title="Copy"
                                    onclick="copyToClipboard('add_secret')">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        {{-- User Portal Password --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">User Portal Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="user_password" id="add_user_password" class="form-control font-monospace" required
                                    placeholder="Min 6 characters">
                                <button type="button" class="btn btn-outline-secondary" title="Generate"
                                    onclick="generatePassword('add_user_password')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" title="Copy"
                                    onclick="copyToClipboard('add_user_password')">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Permission & Max Contacts --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Permission <span class="text-danger">*</span></label>
                            <select name="permission" class="form-select" required>
                                <option value="internal" selected>Internal</option>
                                <option value="local">Local</option>
                                <option value="national">National</option>
                                <option value="international">International</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Contacts</label>
                            <input type="number" name="max_contacts" class="form-control" value="3" min="1" max="10">
                        </div>

                        {{-- Feature Checkboxes --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold d-block mb-2">Features</label>
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="voicemail_enable"
                                        id="add_voicemail" value="yes" checked>
                                    <label class="form-check-label" for="add_voicemail">
                                        <i class="bi bi-voicemail me-1"></i>Voicemail
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="call_waiting"
                                        id="add_call_waiting" value="yes" checked>
                                    <label class="form-check-label" for="add_call_waiting">
                                        <i class="bi bi-telephone-plus me-1"></i>Call Waiting
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="dnd"
                                        id="add_dnd" value="yes">
                                    <label class="form-check-label" for="add_dnd">
                                        <i class="bi bi-slash-circle me-1"></i>Do Not Disturb (DND)
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="sync_contact"
                                        id="add_sync_contact" value="yes" checked>
                                    <label class="form-check-label" for="add_sync_contact">
                                        <i class="bi bi-arrow-repeat me-1"></i>Sync Contact
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="generateAllPasswords()">
                        <i class="bi bi-arrow-repeat me-1"></i>Generate All Passwords
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Extension
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ─── Wave Credentials Modal (shared, loaded via AJAX) ──────── --}}
<div class="modal fade" id="waveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-qr-code me-2"></i>Wave / SIP Credentials
                    <span id="waveExtNum" class="ms-2 fw-light"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Loading spinner --}}
                <div id="waveLoading" class="text-center py-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2 text-muted">Loading credentials…</p>
                </div>

                {{-- Error --}}
                <div id="waveError" class="alert alert-danger d-none"></div>

                {{-- Content --}}
                <div id="waveContent" class="d-none">
                    <table class="table table-bordered table-sm mb-3">
                        <tr>
                            <th class="table-light" style="width:35%">SIP Server</th>
                            <td id="waveServer" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <th class="table-light">Username</th>
                            <td id="waveUsername" class="font-monospace fw-bold"></td>
                        </tr>
                        <tr>
                            <th class="table-light">SIP Password</th>
                            <td>
                                <span id="waveSecret" class="font-monospace"></span>
                                <button class="btn btn-sm btn-outline-secondary ms-2 py-0"
                                    onclick="copyText(document.getElementById('waveSecret').innerText)"
                                    title="Copy password">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th class="table-light">Full Name</th>
                            <td id="waveFullname"></td>
                        </tr>
                    </table>

                    {{-- QR Code --}}
                    <div class="text-center">
                        <p class="text-muted small mb-2">Scan with Grandstream Wave to register</p>
                        <div id="waveQr" class="d-inline-block p-2 border rounded bg-white"></div>
                        <br>
                        <small class="text-muted" id="waveSipUri"></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ─── Scripts ────────────────────────────────────────────────── --}}
@push('scripts')
{{-- QR Code library --}}
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// ── Live Search ────────────────────────────────────────────
document.getElementById('extSearch')?.addEventListener('keyup', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#extensionsTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
});

// ── Password Generator ─────────────────────────────────────
function generatePassword(fieldId) {
    const upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower   = 'abcdefghjkmnpqrstuvwxyz';
    const digits  = '23456789';
    const special = '@#$!';
    const all     = upper + lower + digits + special;

    // Guarantee at least one of each type
    let pwd = [
        upper  [Math.floor(Math.random() * upper.length)],
        lower  [Math.floor(Math.random() * lower.length)],
        digits [Math.floor(Math.random() * digits.length)],
        special[Math.floor(Math.random() * special.length)],
    ];

    // Fill to 12 characters
    for (let i = pwd.length; i < 12; i++) {
        pwd.push(all[Math.floor(Math.random() * all.length)]);
    }

    // Shuffle
    pwd = pwd.sort(() => Math.random() - 0.5).join('');

    const field = document.getElementById(fieldId);
    if (field) {
        field.value = pwd;
        field.type  = 'text';   // show it so user can copy
    }
}

function generateAllPasswords() {
    generatePassword('add_secret');
    generatePassword('add_user_password');
}

function copyToClipboard(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field || !field.value) return;
    navigator.clipboard.writeText(field.value).then(() => {
        const btn = field.nextElementSibling?.nextElementSibling
                 ?? field.parentElement.querySelector('[onclick*="copyToClipboard"]');
        if (btn) {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check text-success"></i>';
            setTimeout(() => btn.innerHTML = orig, 1500);
        }
    });
}

function copyText(text) {
    navigator.clipboard.writeText(text).catch(() => {});
}

// ── Wave Credentials ────────────────────────────────────────
let waveQrInstance = null;

function loadWave(extension, ucmId) {
    // Reset modal
    document.getElementById('waveLoading').classList.remove('d-none');
    document.getElementById('waveError').classList.add('d-none');
    document.getElementById('waveContent').classList.add('d-none');
    document.getElementById('waveExtNum').textContent = '— ' + extension;
    document.getElementById('waveQr').innerHTML = '';

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('waveModal'));
    modal.show();

    // Build URL
    const url = '{{ route("admin.extensions.wave", ["extension" => "__EXT__"]) }}'
        .replace('__EXT__', extension) + '?ucm_id=' + ucmId;

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) throw new Error(data.error);

        document.getElementById('waveServer').textContent   = data.server   || '—';
        document.getElementById('waveUsername').textContent = data.extension || '—';
        document.getElementById('waveSecret').textContent   = data.secret   || '(not available)';
        document.getElementById('waveFullname').textContent = data.fullname || '—';

        const sipUri = data.sip_uri || ('sip:' + data.extension + '@' + data.server);
        document.getElementById('waveSipUri').textContent = sipUri;

        // Generate QR code from the SIP URI
        waveQrInstance = new QRCode(document.getElementById('waveQr'), {
            text:          sipUri,
            width:         200,
            height:        200,
            colorDark:     '#000000',
            colorLight:    '#ffffff',
            correctLevel:  QRCode.CorrectLevel.M,
        });

        document.getElementById('waveLoading').classList.add('d-none');
        document.getElementById('waveContent').classList.remove('d-none');
    })
    .catch(err => {
        document.getElementById('waveLoading').classList.add('d-none');
        const errEl = document.getElementById('waveError');
        errEl.textContent = 'Failed to load credentials: ' + err.message;
        errEl.classList.remove('d-none');
    });
}
</script>
@endpush

@endsection
