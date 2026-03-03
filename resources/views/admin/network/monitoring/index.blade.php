@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1">SNMP Monitoring</h2>
        <p class="text-muted small mb-0">Infrastructure Health & Performance Monitoring</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.network.monitoring.mibs') }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-medical me-1"></i> Managed MIBs
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHostModal">
            <i class="bi bi-plus-lg me-1"></i> Add Monitored Host
        </a>
    </div>
</div>

<div class="row g-4">
    @foreach($hosts as $host)
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-{{ $host->status == 'up' ? 'success' : 'danger' }}-subtle text-{{ $host->status == 'up' ? 'success' : 'danger' }} border border-{{ $host->status == 'up' ? 'success' : 'danger' }}-subtle mb-2">
                                <i class="bi bi-record-fill me-1"></i> {{ strtoupper($host->status) }}
                            </span>
                            <h5 class="card-title mb-0 fw-bold text-dark">{{ $host->name }}</h5>
                            <code class="small text-muted">{{ $host->ip }}</code>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link link-secondary p-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('admin.network.monitoring.show', $host) }}">View Details & Graphs</a></li>
                                <li><a class="dropdown-item edit-host-btn" href="#" 
                                       data-host="{{ json_encode($host) }}"
                                       data-bs-toggle="modal" data-bs-target="#editHostModal">Edit Configuration</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.network.monitoring.hosts.destroy', $host) }}" method="POST" onsubmit="return confirm('Stop monitoring this host?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">Remove Host</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="p-2 bg-light rounded-3 mb-3 small d-flex justify-content-around text-center">
                        <div>
                            <span class="text-muted d-block opacity-75">Type</span>
                            <span class="fw-bold">{{ strtoupper($host->type) }}</span>
                        </div>
                        <div class="border-start ps-3">
                            <span class="text-muted d-block opacity-75">SNMP</span>
                            <span class="fw-bold {{ $host->snmp_enabled ? 'text-success' : 'text-muted' }}">
                                {{ $host->snmp_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>

                    <div class="small mb-3">
                        <span class="text-muted"><i class="bi bi-clock me-1"></i> Last Checked:</span>
                        <span class="text-dark">{{ $host->last_checked_at ? $host->last_checked_at->diffForHumans() : 'Never' }}</span>
                    </div>

                    <a href="{{ route('admin.network.monitoring.show', $host) }}" class="btn btn-outline-primary btn-sm w-100">
                        Analyze Performance <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    @endforeach

    @if($hosts->isEmpty())
        <div class="col-12">
            <div class="card border-0 bg-light text-center py-5">
                <div class="card-body">
                    <i class="bi bi-speedometer2 fs-1 text-muted opacity-25 d-block mb-3"></i>
                    <h5 class="text-dark">No Hosts Monitored</h5>
                    <p class="text-muted mb-4 small">Add a network device, server, or printer to start collecting health metrics.</p>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHostModal">
                        Add Your First Host
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Add Host Modal -->
<div class="modal fade" id="addHostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.network.monitoring.hosts.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title">Add Monitored Host</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Device Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Core Switch" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="gateway">Gateway</option>
                                <option value="switch">Switch</option>
                                <option value="ucm">IP-PBX (UCM)</option>
                                <option value="printer">Printer</option>
                                <option value="server">Server</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">IP Address / Hostname</label>
                            <input type="text" name="ip" class="form-control" placeholder="192.168.1.1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Branch (Optional)</label>
                            <select name="branch_id" class="form-select">
                                <option value="">None / Standalone</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <hr class="my-4">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="snmp_enabled" value="1" id="snmpSwitch" checked>
                                <label class="form-check-label fw-bold" for="snmpSwitch">Enable SNMP Polling</label>
                            </div>
                        </div>

                        <div id="snmpFields" class="row col-12 g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">SNMP Version</label>
                                <select name="snmp_version" class="form-select">
                                    <option value="v2c">v2c (Shared Secret)</option>
                                    <option value="v1">v1 (Legacy)</option>
                                    <option value="v3">v3 (Secure / Auth)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Read Community</label>
                                <input type="password" name="snmp_community" class="form-control" value="public">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save & Start Monitoring</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Host Modal -->
<div class="modal fade" id="editHostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editHostForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-secondary text-white py-3">
                    <h5 class="modal-title">Edit Monitored Host</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Device Name</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Type</label>
                            <select name="type" id="edit-type" class="form-select" required>
                                <option value="gateway">Gateway</option>
                                <option value="switch">Switch</option>
                                <option value="ucm">IP-PBX (UCM)</option>
                                <option value="printer">Printer</option>
                                <option value="server">Server</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">IP Address / Hostname</label>
                            <input type="text" name="ip" id="edit-ip" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Branch</label>
                            <select name="branch_id" id="edit-branch" class="form-select">
                                <option value="">None / Standalone</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <hr class="my-4">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="snmp_enabled" value="1" id="edit-snmpSwitch">
                                <label class="form-check-label fw-bold" for="edit-snmpSwitch">Enable SNMP Polling</label>
                            </div>
                        </div>

                        <div id="edit-snmpFields" class="row col-12 g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">SNMP Version</label>
                                <select name="snmp_version" id="edit-snmpVersion" class="form-select">
                                    <option value="v2c">v2c</option>
                                    <option value="v1">v1</option>
                                    <option value="v3">v3</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Read Community</label>
                                <input type="password" name="snmp_community" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary px-4">Update Host</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const snmpSwitch = document.getElementById('snmpSwitch');
    const snmpFields = document.getElementById('snmpFields');
    
    snmpSwitch.addEventListener('change', function() {
        snmpFields.style.display = this.checked ? 'flex' : 'none';
    });

    // Edit Modal Logic
    document.querySelectorAll('.edit-host-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const host = JSON.parse(this.getAttribute('data-host'));
            const form = document.getElementById('editHostForm');
            
            form.action = `{{ url('admin/network/monitoring/hosts') }}/${host.id}`;
            document.getElementById('edit-name').value = host.name;
            document.getElementById('edit-type').value = host.type;
            document.getElementById('edit-ip').value = host.ip;
            document.getElementById('edit-branch').value = host.branch_id || '';
            document.getElementById('edit-snmpSwitch').checked = host.snmp_enabled;
            document.getElementById('edit-snmpVersion').value = host.snmp_version;
            
            document.getElementById('edit-snmpFields').style.display = host.snmp_enabled ? 'flex' : 'none';
        });
    });

    document.getElementById('edit-snmpSwitch').addEventListener('change', function() {
        document.getElementById('edit-snmpFields').style.display = this.checked ? 'flex' : 'none';
    });
});
</script>
@endpush
@endsection
