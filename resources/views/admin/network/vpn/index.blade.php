@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1">VPN Hub</h2>
        <p class="text-muted small mb-0">Central IPsec VPN Hub & Tunnel Orchestration</p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.network.vpn.reload') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i> Reload Config
            </button>
        </form>
        <a href="{{ route('admin.network.vpn.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add VPN Tunnel
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Branch</th>
                        <th>Tunnel Name</th>
                        <th>Remote IP</th>
                        <th>Subnets (Remote / Local)</th>
                        <th>Status</th>
                        <th>Last Checked</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tunnels as $tunnel)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    {{ $tunnel->branch->name }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $tunnel->name }}</div>
                            </td>
                            <td>
                                <code class="small">{{ $tunnel->remote_public_ip }}</code>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-primary">{{ $tunnel->remote_subnet }}</span>
                                    <span class="text-muted mx-1">/</span>
                                    <span class="text-success">{{ $tunnel->local_subnet }}</span>
                                </div>
                            </td>
                            <td>
                                <div id="status-{{ $tunnel->id }}" class="d-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm text-muted me-2" role="status"></span>
                                    <span class="text-muted small">Checking...</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    {{ $tunnel->last_checked_at ? $tunnel->last_checked_at->diffForHumans() : 'Never' }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <form action="{{ route('admin.network.vpn.up', $tunnel) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success" title="Initiate Tunnel">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.network.vpn.down', $tunnel) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger" title="Terminate Tunnel">
                                            <i class="bi bi-stop-fill"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.network.vpn.edit', $tunnel) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.network.vpn.destroy', $tunnel) }}" method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Permanently delete this VPN tunnel?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-3">
                                    <i class="bi bi-diagram-3 fs-1 d-block mb-3 opacity-25"></i>
                                    No VPN tunnels configured yet.
                                </div>
                                <a href="{{ route('admin.network.vpn.create') }}" class="btn btn-primary btn-sm">Add Your First Tunnel</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tunnels = @json($tunnels->pluck('id'));
    
    tunnels.forEach(id => {
        fetchStatus(id);
    });

    function fetchStatus(id) {
        const container = document.getElementById(`status-${id}`);
        fetch(`{{ url('admin/network/vpn') }}/${id}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.is_up) {
                    container.innerHTML = `
                        <span class="badge rounded-circle bg-success p-1 me-2" style="width: 10px; height: 10px;"></span>
                        <span class="text-success small fw-bold">UP</span>
                    `;
                } else {
                    container.innerHTML = `
                        <span class="badge rounded-circle bg-danger p-1 me-2" style="width: 10px; height: 10px;"></span>
                        <span class="text-danger small fw-bold">DOWN</span>
                    `;
                }
            })
            .catch(error => {
                container.innerHTML = `<span class="text-danger small">Error</span>`;
            });
    }
});
</script>
@endpush
@endsection
