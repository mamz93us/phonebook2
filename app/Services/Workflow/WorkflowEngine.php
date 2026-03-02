<?php

namespace App\Services\Workflow;

use App\Jobs\ExecuteWorkflowJob;
use App\Models\WorkflowLog;
use App\Models\WorkflowRequest;
use App\Models\WorkflowStep;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class WorkflowEngine
{
    // Approval chain definition: type => [role, role, ...]
    private const CHAINS = [
        'create_user'       => ['hr', 'it_manager'],
        'delete_user'       => ['it_manager', 'super_admin'],
        'license_change'    => ['it_manager'],
        'asset_assign'      => ['manager'],
        'asset_return'      => ['manager'],
        'extension_create'  => ['it_manager'],
        'extension_delete'  => ['it_manager'],
        'other'             => ['it_manager'],
    ];

    public function __construct(private NotificationService $notifications) {}

    // ─────────────────────────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────────────────────────

    public function createRequest(
        string $type,
        array $payload,
        ?int $branchId,
        int $requestedBy,
        string $title,
        ?string $description = null
    ): WorkflowRequest {
        $chain = self::CHAINS[$type] ?? ['it_manager'];

        $workflow = WorkflowRequest::create([
            'type'         => $type,
            'title'        => $title,
            'description'  => $description,
            'payload'      => $payload,
            'branch_id'    => $branchId,
            'requested_by' => $requestedBy,
            'status'       => 'pending',
            'current_step' => 1,
            'total_steps'  => count($chain),
        ]);

        $this->buildApprovalChain($workflow, $chain);
        $this->logEvent($workflow, 'info', "Workflow created by user #{$requestedBy}: {$title}");

        // Notify approvers of step 1
        $this->notifyApprovers($workflow, 1);

        return $workflow;
    }

    // ─────────────────────────────────────────────────────────────
    // Build approval steps
    // ─────────────────────────────────────────────────────────────

    public function buildApprovalChain(WorkflowRequest $workflow, array $chain): void
    {
        foreach ($chain as $i => $role) {
            WorkflowStep::create([
                'workflow_id'   => $workflow->id,
                'step_number'   => $i + 1,
                'approver_role' => $role,
                'status'        => 'pending',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Approve / Reject
    // ─────────────────────────────────────────────────────────────

    public function approveStep(WorkflowRequest $workflow, \App\Models\User $user, ?string $comments = null): void
    {
        $step = $workflow->currentStepRecord();

        if (!$step || $step->status !== 'pending') {
            throw new \RuntimeException('No pending step to approve.');
        }

        $step->update([
            'status'   => 'approved',
            'acted_by' => $user->id,
            'acted_at' => now(),
            'comments' => $comments,
        ]);

        $this->logEvent($workflow, 'success', "Step {$step->step_number} approved by {$user->name}" . ($comments ? ": {$comments}" : ''));

        // Notify requester
        $this->notifications->notify(
            $workflow->requested_by,
            'approval_action',
            "Step {$step->step_number} Approved — {$workflow->title}",
            "{$user->name} approved step {$step->step_number} ({$step->approverRoleLabel()}).",
            route('admin.workflows.show', $workflow->id),
            'info'
        );

        $this->moveToNextStep($workflow);
    }

    public function rejectStep(WorkflowRequest $workflow, \App\Models\User $user, ?string $comments = null): void
    {
        $step = $workflow->currentStepRecord();

        if (!$step || $step->status !== 'pending') {
            throw new \RuntimeException('No pending step to reject.');
        }

        $step->update([
            'status'   => 'rejected',
            'acted_by' => $user->id,
            'acted_at' => now(),
            'comments' => $comments,
        ]);

        $workflow->update(['status' => 'rejected']);
        $this->logEvent($workflow, 'error', "Step {$step->step_number} rejected by {$user->name}" . ($comments ? ": {$comments}" : ''));

        // Notify requester
        $this->notifications->notify(
            $workflow->requested_by,
            'approval_action',
            "Request Rejected — {$workflow->title}",
            "{$user->name} rejected this request at step {$step->step_number}." . ($comments ? " Reason: {$comments}" : ''),
            route('admin.workflows.show', $workflow->id),
            'warning'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Advance to next step
    // ─────────────────────────────────────────────────────────────

    public function moveToNextStep(WorkflowRequest $workflow): void
    {
        $workflow->refresh();
        $nextStep = $workflow->current_step + 1;

        if ($nextStep > $workflow->total_steps) {
            // All steps approved — execute
            $workflow->update(['status' => 'approved']);
            $this->logEvent($workflow, 'success', 'All approval steps completed. Queuing execution.');
            $this->executeWorkflow($workflow);
        } else {
            // Move to next step
            $workflow->update(['current_step' => $nextStep]);
            $this->logEvent($workflow, 'info', "Advanced to step {$nextStep}.");
            $this->notifyApprovers($workflow, $nextStep);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Execute
    // ─────────────────────────────────────────────────────────────

    public function executeWorkflow(WorkflowRequest $workflow): void
    {
        $workflow->update(['status' => 'executing']);
        $this->logEvent($workflow, 'info', 'Dispatching workflow execution job.');
        ExecuteWorkflowJob::dispatch($workflow->id);
    }

    // ─────────────────────────────────────────────────────────────
    // Mark failed
    // ─────────────────────────────────────────────────────────────

    public function markFailed(WorkflowRequest $workflow, string $message): void
    {
        $workflow->update(['status' => 'failed']);
        $this->logEvent($workflow, 'error', "Workflow failed: {$message}");

        $this->notifications->notifyAdmins(
            'workflow_failed',
            "Workflow Failed — {$workflow->title}",
            "Workflow #{$workflow->id} ({$workflow->typeLabel()}) failed during execution: {$message}",
            route('admin.workflows.show', $workflow->id),
            'critical'
        );

        $this->notifications->notify(
            $workflow->requested_by,
            'workflow_failed',
            "Your Request Failed — {$workflow->title}",
            "Your request could not be completed: {$message}",
            route('admin.workflows.show', $workflow->id),
            'critical'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Logging
    // ─────────────────────────────────────────────────────────────

    public function logEvent(WorkflowRequest $workflow, string $level, string $message, array $context = []): void
    {
        WorkflowLog::create([
            'workflow_id' => $workflow->id,
            'level'       => $level,
            'message'     => $message,
            'context'     => empty($context) ? null : $context,
            'created_at'  => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Notify approvers for a given step
    // ─────────────────────────────────────────────────────────────

    private function notifyApprovers(WorkflowRequest $workflow, int $stepNumber): void
    {
        $step = WorkflowStep::where('workflow_id', $workflow->id)
            ->where('step_number', $stepNumber)
            ->first();

        if (!$step) return;

        // If assigned to specific user
        if ($step->approver_id) {
            $this->notifications->notify(
                $step->approver_id,
                'approval_request',
                "Approval Required — {$workflow->title}",
                "A workflow request requires your approval (Step {$stepNumber}: {$step->approverRoleLabel()}).",
                route('admin.workflows.show', $workflow->id),
                'warning'
            );
            return;
        }

        // Role-based: notify all admins (simplified)
        $this->notifications->notifyAdmins(
            'approval_request',
            "Approval Required — {$workflow->title}",
            "A workflow request requires {$step->approverRoleLabel()} approval (Step {$stepNumber}).",
            route('admin.workflows.show', $workflow->id),
            'warning'
        );
    }
}
