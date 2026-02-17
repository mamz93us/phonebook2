@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Activity Logs</h1>
</div>

<!-- Activity Logs Table -->
@if($logs->count() > 0)
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">Date/Time</th>
                            <th style="width: 15%;">User</th>
                            <th style="width: 15%;">Action</th>
                            <th style="width: 20%;">Subject</th>
                            <th style="width: 35%;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->created_at->format('d M Y') }}</small><br>
                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                </td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>
                                    @if($log->action == 'created')
                                        <span class="badge bg-success">Created</span>
                                    @elseif($log->action == 'updated')
                                        <span class="badge bg-info">Updated</span>
                                    @elseif($log->action == 'deleted')
                                        <span class="badge bg-danger">Deleted</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($log->action) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $log->subject_type)) }}</strong>
                                    @if($log->subject_id)
                                        <br><small class="text-muted">ID: {{ $log->subject_id }}</small>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $log->description ?? '-' }}</small>
                                    @if($log->changes)
                                        <br>
                                        <small class="text-muted">
                                            @if(is_array($log->changes))
                                                {{ Str::limit(json_encode($log->changes), 100) }}
                                            @else
                                                {{ Str::limit($log->changes, 100) }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-center">
        {{ $logs->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="alert alert-info text-center">
        <p class="mb-0">No activity logs found.</p>
    </div>
@endif

@endsection
