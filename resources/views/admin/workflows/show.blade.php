@extends('layouts.admin')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-diagram-2-fill me-2 text-primary"></i>{{ $workflow->title }}</h4>
        <small class="text-muted">Workflow #{{ $workflow->id }} &bull; {{ $workflow->typeLabel() }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.workflows.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">
    {{-- Left: Metadata --}}
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent">
                <strong><i class="bi bi-info-circle me-1"></i>Request Details</strong>
            </div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge {{ $workflow->statusBadgeClass() }}">{{ ucfirst($workflow->status) }}</span></dd>
                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7"><span class="badge {{ $workflow->typeBadgeClass() }}">{{ $workflow->typeLabel() }}</span></dd>
                    <dt class="col-5 text-muted">Requested by</dt>
                    <dd class="col-7 fw-semibold">{{ $workflow->requester?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Branch</dt>
                    <dd class="col-7">{{ $workflow->branch?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7">{{ $workflow->created_at->format('d M Y H:i') }}</dd>
                    <dt class="col-5 text-muted">Updated</dt>
                    <dd class="col-7">{{ $workflow->updated_at->format('d M Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($workflow->description)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent"><strong><i class="bi bi-chat-text me-1"></i>Description</strong></div>
            <div class="card-body small">{{ $workflow->description }}</div>
        </div>
        @endif

        @if($workflow->payload)
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong><i class="bi bi-code me-1"></i>Request Data</strong></div>
            <div class="card-body p-0">
                <pre class="m-0 p-3 small" style="background:#f8f9fa;border-radius:0 0 .375rem .375rem;font-size:.75rem;overflow-x:auto">{{ json_encode($workflow->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Timeline + Logs --}}
    <div class="col-12 col-lg-8">
        {{-- Timeline --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <strong><i class="bi bi-signpost-split me-1"></i>Approval Timeline</strong>
                <span class="badge bg-secondary">{{ $workflow->current_step }}/{{ $workflow->total_steps }} steps</span>
            </div>
            <div class="card-body">
                @foreach($workflow->steps as $step)
                <div class="d-flex gap-3 mb-3">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                             style="width:36px;height:36px;background:{{ $step->status === 'approved' ? '#198754' : ($step->status === 'rejected' ? '#dc3545' : ($step->status === 'pending' ? '#ffc107' : '#6c757d')) }}">
                            {{ $step->step_number }}
                        </div>
                        @if(!$loop->last)<div style="width:2px;height:32px;background:#dee2e6;margin:4px auto"></div>@endif
                    </div>
                    <div class="flex-grow-1 pb-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <strong class="small">{{ $step->approverRoleLabel() }}</strong>
                            <span class="badge {{ $step->statusBadgeClass() }} small">{{ ucfirst($step->status) }}</span>
                        </div>
                        @if($step->actor)
                        <div class="text-muted small">
                            By: {{ $step->actor->name }}
                            @if($step->acted_at) &bull; {{ $step->acted_at->format('d M Y H:i') }}@endif
                        </div>
                        @endif
                        @if($step->comments)
                        <div class="alert alert-light py-1 px-2 mt-1 small mb-0">
                            <i class="bi bi-chat-text me-1"></i>{{ $step->comments }}
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Approve/Reject buttons --}}
                @if($canApprove)
                <div class="border-top pt-3 mt-2 d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.workflows.approve', $workflow->id) }}" class="d-flex gap-2 flex-grow-1">
                        @csrf
                        <input type="text" name="comments" class="form-control form-control-sm" placeholder="Optional comment...">
                        <button type="submit" class="btn btn-sm btn-success text-nowrap">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-lg me-1"></i>Reject
                    </button>
                </div>
                @endif
            </div>
        </div>

        {{-- Execution Logs --}}
        @if($workflow->logs->isNotEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent">
                <strong><i class="bi bi-terminal me-1"></i>Execution Log</strong>
                <span class="badge bg-secondary ms-2">{{ $workflow->logs->count() }}</span>
            </div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                <table class="table table-sm mb-0 small">
                    <tbody>
                        @foreach($workflow->logs as $log)
                        <tr>
                            <td class="text-muted ps-3" style="width:140px;white-space:nowrap">{{ $log->created_at->format('H:i:s') }}</td>
                            <td style="width:90px"><span class="badge {{ $log->levelBadgeClass() }} small">{{ strtoupper($log->level) }}</span></td>
                            <td class="pe-3">{{ $log->message }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Reject modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.workflows.reject', $workflow->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">Reason for rejection</label>
                    <textarea name="comments" class="form-control form-control-sm" rows="3" placeholder="Required..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
