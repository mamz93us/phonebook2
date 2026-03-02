@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-patch-check-fill me-2 text-primary"></i>Provisioning Licenses</h4>
        <small class="text-muted">Select the default Microsoft 365 license assigned when provisioning new users</small>
    </div>
    <a href="{{ route('admin.settings.index') }}#provisioning" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Settings
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Current default --}}
<div class="alert {{ $settings->graph_default_license_sku ? 'alert-info' : 'alert-warning' }} mb-4">
    <i class="bi bi-{{ $settings->graph_default_license_sku ? 'info-circle' : 'exclamation-triangle' }}-fill me-2"></i>
    @if($settings->graph_default_license_sku)
        <strong>Current default:</strong>
        @php
            $currentName = collect($licenses)->firstWhere('skuId', $settings->graph_default_license_sku)['skuPartNumber']
                         ?? $settings->graph_default_license_sku;
        @endphp
        <span class="fw-semibold">{{ $currentName }}</span>
        <code class="ms-2 small">{{ $settings->graph_default_license_sku }}</code>
    @else
        <strong>No default license set.</strong> Users will be provisioned without a license until one is selected.
    @endif
</div>

@if($error)
{{-- Azure error --}}
<div class="alert alert-danger">
    <i class="bi bi-x-circle-fill me-2"></i>
    <strong>Could not fetch licenses from Azure:</strong> {{ $error }}
    <div class="mt-2 small">Check that Microsoft Graph credentials are configured in
        <a href="{{ route('admin.settings.index') }}#graph" class="alert-link">General Settings → Microsoft Graph</a>.
    </div>
</div>

{{-- Allow manual entry even if Azure call failed --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><strong><i class="bi bi-keyboard me-1"></i>Set License SKU Manually</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.provisioning-licenses.save') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">License SKU ID (GUID)</label>
                    <input type="text" name="license_sku" class="form-control font-monospace"
                           value="{{ old('license_sku', $settings->graph_default_license_sku) }}"
                           placeholder="e.g. 6fd2c87f-b296-42f0-b197-1e91e994b900">
                    <div class="form-text">Find SKU IDs in the Azure Portal under Licenses, or via Microsoft's published SKU list.</div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                    @if($settings->graph_default_license_sku)
                    <button type="submit" name="license_sku" value="" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-lg me-1"></i>Clear
                    </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@elseif(empty($licenses))
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>No licenses found in your Azure tenant.
</div>

@else
{{-- License table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
        <strong><i class="bi bi-list-check me-1"></i>Available Licenses</strong>
        <span class="badge bg-secondary">{{ count($licenses) }} SKU{{ count($licenses) !== 1 ? 's' : '' }}</span>
    </div>
    <div class="card-body p-0">
        <form method="POST" action="{{ route('admin.settings.provisioning-licenses.save') }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:40px"></th>
                            <th>License Name</th>
                            <th>SKU ID</th>
                            <th class="text-center">Used / Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($licenses as $lic)
                        @php
                            $skuId      = $lic['skuId'] ?? '';
                            $skuName    = $lic['skuPartNumber'] ?? $skuId;
                            $consumed   = $lic['consumedUnits'] ?? 0;
                            $enabled    = $lic['prepaidUnits']['enabled']  ?? 0;
                            $warning    = $lic['prepaidUnits']['warning']  ?? 0;
                            $suspended  = $lic['prepaidUnits']['suspended'] ?? 0;
                            $total      = $enabled + $warning + $suspended;
                            $capStatus  = $lic['capabilityStatus'] ?? 'Enabled';
                            $isCurrent  = $skuId === $settings->graph_default_license_sku;
                        @endphp
                        <tr class="{{ $isCurrent ? 'table-primary' : '' }}">
                            <td class="ps-3 text-center">
                                <div class="form-check d-flex justify-content-center mb-0">
                                    <input class="form-check-input" type="radio" name="license_sku"
                                           id="lic_{{ $loop->index }}" value="{{ $skuId }}"
                                           {{ $isCurrent ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <label for="lic_{{ $loop->index }}" class="fw-semibold mb-0" style="cursor:pointer">
                                    {{ $skuName }}
                                </label>
                                @if($isCurrent)
                                <span class="badge bg-primary ms-1">Current Default</span>
                                @endif
                            </td>
                            <td><code class="small" style="font-size:.7rem">{{ $skuId }}</code></td>
                            <td class="text-center">
                                <span class="{{ $consumed >= $total && $total > 0 ? 'text-danger fw-semibold' : '' }}">
                                    {{ $consumed }}
                                </span>
                                <span class="text-muted">/ {{ $total ?: '∞' }}</span>
                            </td>
                            <td class="text-center">
                                @if($capStatus === 'Enabled')
                                <span class="badge bg-success">Enabled</span>
                                @elseif($capStatus === 'Warning')
                                <span class="badge bg-warning text-dark">Warning</span>
                                @elseif($capStatus === 'Suspended')
                                <span class="badge bg-danger">Suspended</span>
                                @else
                                <span class="badge bg-secondary">{{ $capStatus }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg me-1"></i>Save Default License
                </button>
                <button type="submit" name="license_sku" value="" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>Clear Default
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
