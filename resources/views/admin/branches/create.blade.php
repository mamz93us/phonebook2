@extends('layouts.admin')

@section('content')
<h3>Add Branch</h3>

<form action="{{ route('admin.branches.store') }}" method="POST" class="mt-3">
    @csrf

    <div class="mb-3">
        <label class="form-label">Branch ID (manual)</label>
        <input type="number" name="id" class="form-control" value="{{ old('id') }}" required>
        @error('id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Branch Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>

    <button class="btn btn-success">Save</button>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
