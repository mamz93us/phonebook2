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

@endsection
