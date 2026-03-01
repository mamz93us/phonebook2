@extends('layouts.admin')
@section('content')

@php $editing = isset($device); @endphp

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.devices.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-cpu me-2 text-primary"></i>{{ $editing ? 'Edit Device' : 'Add Device' }}
    </h4>
</div>

<div class="card shadow-sm" style="max-width:700px">
    <div class="card-body">
        <form method="POST" action="{{ $editing ? route('admin.devices.update', $device) : route('admin.devices.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach(['ucm','switch','router','firewall','ap','printer','server','other'] as $t)
                        <option value="{{ $t }}" {{ old('type', $device->type ?? '') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active"      {{ old('status', $device->status ?? 'active') == 'active'      ? 'selected' : '' }}>Active</option>
                        <option value="maintenance" {{ old('status', $device->status ?? '') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="retired"     {{ old('status', $device->status ?? '') == 'retired'     ? 'selected' : '' }}>Retired</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $device->name ?? '') }}" required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-control" value="{{ old('model', $device->model ?? '') }}" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $device->serial_number ?? '') }}" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-control font-monospace" value="{{ old('ip_address', $device->ip_address ?? '') }}" placeholder="192.168.1.1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" class="form-control font-monospace" value="{{ old('mac_address', $device->mac_address ?? '') }}" maxlength="20">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $device->branch_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location Description</label>
                    <input type="text" name="location_description" class="form-control" value="{{ old('location_description', $device->location_description ?? '') }}" maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $device->notes ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Device' }}</button>
                <a href="{{ route('admin.devices.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
