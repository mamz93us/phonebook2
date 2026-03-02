<?php

namespace App\Services\Workflow;

use App\Models\Employee;
use App\Models\WorkflowRequest;
use App\Services\Identity\GraphService;
use Illuminate\Support\Facades\Log;

class UserProvisioningService
{
    public function __construct(private WorkflowEngine $engine) {}

    // ─────────────────────────────────────────────────────────────
    // Provision new user
    // ─────────────────────────────────────────────────────────────

    public function provisionUser(WorkflowRequest $workflow): void
    {
        $payload = $workflow->payload ?? [];

        $this->engine->logEvent($workflow, 'info', 'Starting user provisioning.');

        // Step 1: Create Azure user
        $this->engine->logEvent($workflow, 'info', 'Creating Azure AD user...');
        try {
            $graph = new GraphService();

            $displayName = trim(($payload['first_name'] ?? '') . ' ' . ($payload['last_name'] ?? ''));
            $upn         = $payload['user_principal_name'] ?? null;
            $password    = $payload['initial_password'] ?? \Illuminate\Support\Str::random(16) . '!1';

            if (!$upn) {
                throw new \RuntimeException('User Principal Name is required.');
            }

            $azureUser = $graph->createUser([
                'displayName'       => $displayName,
                'userPrincipalName' => $upn,
                'mailNickname'      => explode('@', $upn)[0],
                'password'          => $password,
                'usageLocation'     => $payload['usage_location'] ?? 'US',
                'jobTitle'          => $payload['job_title'] ?? null,
                'department'        => $payload['department'] ?? null,
                'accountEnabled'    => true,
            ]);

            $azureId = $azureUser['id'] ?? null;
            if (!$azureId) {
                throw new \RuntimeException('Azure user created but no ID returned.');
            }

            $this->engine->logEvent($workflow, 'success', "Azure user created: {$upn} (ID: {$azureId})");
            $workflow->payload = array_merge($payload, ['azure_id' => $azureId]);
            $workflow->save();

        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to create Azure user: ' . $e->getMessage());
        }

        // Step 2: Assign license
        if (!empty($payload['license_sku'])) {
            $this->engine->logEvent($workflow, 'info', 'Assigning license...');
            try {
                $graph->assignLicense($workflow->payload['azure_id'], $payload['license_sku']);
                $this->engine->logEvent($workflow, 'success', 'License assigned: ' . $payload['license_sku']);
            } catch (\Throwable $e) {
                $this->engine->logEvent($workflow, 'warning', 'License assignment failed (non-fatal): ' . $e->getMessage());
            }
        }

        // Step 3: Create employee record
        $this->engine->logEvent($workflow, 'info', 'Creating employee record...');
        try {
            Employee::create([
                'azure_id'      => $workflow->payload['azure_id'],
                'name'          => $displayName ?? 'Unknown',
                'email'         => $upn ?? null,
                'branch_id'     => $workflow->branch_id,
                'department_id' => $payload['department_id'] ?? null,
                'job_title'     => $payload['job_title'] ?? null,
                'status'        => 'active',
                'hired_date'    => now()->toDateString(),
            ]);
            $this->engine->logEvent($workflow, 'success', 'Employee record created.');
        } catch (\Throwable $e) {
            $this->engine->logEvent($workflow, 'warning', 'Employee record creation failed (non-fatal): ' . $e->getMessage());
        }

        $this->engine->logEvent($workflow, 'success', 'User provisioning complete.');
    }

    // ─────────────────────────────────────────────────────────────
    // Deprovision user
    // ─────────────────────────────────────────────────────────────

    public function deprovisionUser(WorkflowRequest $workflow): void
    {
        $payload = $workflow->payload ?? [];
        $azureId = $payload['azure_id'] ?? null;

        $this->engine->logEvent($workflow, 'info', 'Starting user deprovisioning.');

        if ($azureId) {
            // Step 1: Disable Azure user
            $this->engine->logEvent($workflow, 'info', 'Disabling Azure user...');
            try {
                $graph = new GraphService();
                $graph->disableUser($azureId);
                $this->engine->logEvent($workflow, 'success', 'Azure user disabled.');
            } catch (\Throwable $e) {
                throw new \RuntimeException('Failed to disable Azure user: ' . $e->getMessage());
            }

            // Step 2: Update employee record
            $employee = Employee::where('azure_id', $azureId)->first();
            if ($employee) {
                $employee->update([
                    'status'           => 'terminated',
                    'terminated_date'  => now()->toDateString(),
                ]);
                $this->engine->logEvent($workflow, 'success', 'Employee record updated to terminated.');

                // Step 3: Flag assets as pending return
                $employee->activeAssets()->update(['notes' => 'PENDING RETURN — employee terminated']);
                $this->engine->logEvent($workflow, 'info', 'Active asset assignments flagged for return.');
            }
        }

        $this->engine->logEvent($workflow, 'success', 'User deprovisioning complete.');
    }
}
