@extends('layouts.admin')
@section('content')

@php $editing = isset($printer); @endphp

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.printers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-printer-fill me-2 text-primary"></i>{{ $editing ? 'Edit Printer' : 'Add Printer' }}
    </h4>
</div>

<div class="card shadow-sm" style="max-width:750px">
    <div class="card-body">
        <form method="POST" action="{{ $editing ? route('admin.printers.update', $printer) : route('admin.printers.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="row g-3">

                {{-- Name --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Printer Name <span class="text-danger">*</span></label>
                    <input type="text" name="printer_name" class="form-control @error('printer_name') is-invalid @enderror"
                           value="{{ old('printer_name', $printer->printer_name ?? '') }}" required maxlength="255">
                    @error('printer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Manufacturer + Model --}}
                <div class="col-md-6">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control"
                           value="{{ old('manufacturer', $printer->manufacturer ?? '') }}" maxlength="100"
                           placeholder="e.g. HP, Canon, Epson">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-control"
                           value="{{ old('model', $printer->model ?? '') }}" maxlength="100"
                           placeholder="e.g. LaserJet Pro M404n">
                </div>

                {{-- Serial + Toner --}}
                <div class="col-md-6">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control font-monospace"
                           value="{{ old('serial_number', $printer->serial_number ?? '') }}" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Toner Model</label>
                    <input type="text" name="toner_model" class="form-control"
                           value="{{ old('toner_model', $printer->toner_model ?? '') }}" maxlength="100"
                           placeholder="e.g. CF258A">
                </div>

                {{-- Network --}}
                <div class="col-md-6">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-control font-monospace"
                           value="{{ old('ip_address', $printer->ip_address ?? '') }}" placeholder="192.168.1.100">
                    @error('ip_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" class="form-control font-monospace"
                           value="{{ old('mac_address', $printer->mac_address ?? '') }}" maxlength="20"
                           placeholder="AA:BB:CC:DD:EE:FF">
                </div>

                {{-- Location --}}
                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $printer->branch_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Floor</label>
                    <input type="text" name="floor" class="form-control"
                           value="{{ old('floor', $printer->floor ?? '') }}" maxlength="50"
                           placeholder="e.g. 2nd Floor">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Room</label>
                    <input type="text" name="room" class="form-control"
                           value="{{ old('room', $printer->room ?? '') }}" maxlength="50"
                           placeholder="e.g. Finance Office">
                </div>

                {{-- Department --}}
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control"
                           value="{{ old('department', $printer->department ?? '') }}" maxlength="100"
                           placeholder="e.g. HR, Finance, IT">
                </div>

                {{-- Notes --}}
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $printer->notes ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Add Printer' }}</button>
                <a href="{{ route('admin.printers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
