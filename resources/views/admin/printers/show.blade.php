@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.printers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <i class="bi bi-printer-fill fs-4 text-primary"></i>
        <h4 class="mb-0 fw-bold">{{ $printer->printer_name }}</h4>
    </div>
    @can('manage-printers')
    <a href="{{ route('admin.printers.edit', $printer) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">

    {{-- Printer Info --}}
    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Printer Info</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless small mb-0">
                    <tr><th class="text-muted" style="width:38%">Manufacturer</th><td>{{ $printer->manufacturer ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Model</th><td>{{ $printer->model ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Serial</th><td class="font-monospace">{{ $printer->serial_number ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Toner</th><td>{{ $printer->toner_model ?: '—' }}</td></tr>
                    <tr><th class="text-muted">IP</th><td class="font-monospace">{{ $printer->ip_address ?: '—' }}</td></tr>
                    <tr><th class="text-muted">MAC</th><td class="font-monospace">{{ $printer->mac_address ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Branch</th><td>{{ $printer->branch?->name ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Location</th><td>{{ $printer->locationLabel() }}</td></tr>
                    <tr><th class="text-muted">Department</th><td>{{ $printer->department ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Added</th><td>{{ $printer->created_at->format('d M Y') }}</td></tr>
                </table>
                @if($printer->notes)
                <hr class="my-2">
                <p class="small text-muted mb-0">{{ $printer->notes }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Credentials --}}
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-key-fill me-2"></i>Credentials</h6>
                @can('manage-credentials')
                @if($printer->device)
                <a href="{{ route('admin.credentials.create') }}?device_id={{ $printer->device->id }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-lg"></i> Add
                </a>
                @endif
                @endcan
            </div>
            <div class="card-body p-0">
                @php $credentials = $printer->device?->credentials ?? collect(); @endphp
                @if($credentials->isEmpty())
                <div class="text-center py-4 text-muted small">No credentials linked to this printer.</div>
                @else
                <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>Title</th><th>Category</th><th>Username</th><th>Added by</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($credentials as $cred)
                        <tr>
                            <td class="fw-semibold">{{ $cred->title }}</td>
                            <td><span class="badge {{ $cred->categoryBadgeClass() }}">{{ $cred->categoryLabel() }}</span></td>
                            <td class="font-monospace text-muted">{{ $cred->username ?: '—' }}</td>
                            <td class="text-muted">{{ $cred->creator?->name ?: '—' }}</td>
                            <td>
                                @can('manage-credentials')
                                <a href="{{ route('admin.credentials.edit', $cred) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

@can('manage-printers')
<div class="mt-4">
    <form method="POST" action="{{ route('admin.printers.destroy', $printer) }}"
          onsubmit="return confirm('Delete printer \'{{ addslashes($printer->printer_name) }}\'? This cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete Printer</button>
    </form>
</div>
@endcan
@endsection
