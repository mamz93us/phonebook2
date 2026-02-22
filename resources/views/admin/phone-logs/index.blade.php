@extends('layouts.admin')

@section('title', 'Phone XML Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Phone XML Request Logs</h4>

    <form method="POST" action="{{ route('admin.phone-logs.sync') }}" id="sync-form">
        @csrf
        <button type="submit" class="btn btn-primary" id="sync-btn">
            🔄 Sync SIP Accounts
        </button>
    </form>
</div>

<script>
document.getElementById('sync-form').addEventListener('submit', function () {
    var btn = document.getElementById('sync-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Syncing… (may take up to 1 min)';
});
</script>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th class="px-3">MAC Address</th>
                    <th>Model</th>
                    <th>SIP Accounts (from GDMS)</th>
                    <th>Last Request</th>
                    <th class="text-center">Total Requests</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $macAccounts = $accounts[$log->mac] ?? collect();
                    @endphp
                    <tr>
                        {{-- MAC --}}
                        <td class="font-monospace px-3">
                            {{ strtoupper(implode(':', str_split($log->mac, 2))) }}
                        </td>

                        {{-- Model --}}
                        <td>{{ $log->model ?? '—' }}</td>

                        {{-- SIP Accounts --}}
                        <td>
                            @if ($macAccounts->isEmpty())
                                <span class="text-muted small">Not synced yet</span>
                            @else
                                @foreach ($macAccounts as $acc)
                                    @php
                                        $contact = $acc->sip_user_id ? ($contactsByPhone[$acc->sip_user_id] ?? null) : null;
                                        $isReg   = strtolower($acc->account_status ?? '') === 'registered';
                                    @endphp
                                    <div class="mb-1 d-flex align-items-center gap-2 flex-wrap">
                                        {{-- Account badge --}}
                                        <span class="badge bg-secondary">Acc #{{ $acc->account_index }}</span>

                                        {{-- SIP user ID / extension --}}
                                        @if ($acc->sip_user_id)
                                            <code>{{ $acc->sip_user_id }}</code>
                                        @else
                                            <span class="text-muted small">(empty)</span>
                                        @endif

                                        {{-- Server --}}
                                        @if ($acc->sip_server)
                                            <span class="text-muted small">@ {{ $acc->sip_server }}</span>
                                        @endif

                                        {{-- Registration status --}}
                                        @if ($acc->account_status)
                                            <span class="badge {{ $isReg ? 'bg-success' : 'bg-danger' }}">
                                                {{ $acc->account_status }}
                                            </span>
                                        @endif

                                        {{-- Local flag --}}
                                        @if ($acc->is_local)
                                            <span class="badge bg-info text-dark">Local</span>
                                        @endif

                                        {{-- Linked contact name --}}
                                        @if ($contact)
                                            <span class="fw-semibold text-primary">
                                                &#x1F464; {{ $contact->first_name }} {{ $contact->last_name }}
                                            </span>
                                        @elseif ($acc->sip_user_id)
                                            <span class="text-warning small">No contact match</span>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="text-muted" style="font-size:0.75rem">
                                    Synced: {{ $macAccounts->first()->fetched_at?->diffForHumans() ?? '—' }}
                                </div>
                            @endif
                        </td>

                        {{-- Last Request --}}
                        <td class="text-nowrap">{{ $log->last_request_at }}</td>

                        {{-- Total --}}
                        <td class="text-center">{{ $log->total_requests }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No phone requests logged yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
