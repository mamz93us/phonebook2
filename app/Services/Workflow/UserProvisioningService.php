<?php

namespace App\Services\Workflow;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\IdentityUser;
use App\Models\Setting;
use App\Models\UcmServer;
use App\Models\WorkflowRequest;
use App\Services\Identity\GraphService;
use App\Services\NotificationService;
use Illuminate\Support\Str;

class UserProvisioningService
{
    public function __construct(
        private WorkflowEngine              $engine,
        private ExtensionProvisioningService $extProvisioning,
        private NotificationService          $notifications
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Provision new user (7 steps)
    // ─────────────────────────────────────────────────────────────

    public function provisionUser(WorkflowRequest $workflow): void
    {
        $payload  = $workflow->payload ?? [];
        $settings = Setting::get();

        $this->engine->logEvent($workflow, 'info', 'Starting user provisioning.');

        $firstName   = trim($payload['first_name']   ?? '');
        $lastName    = trim($payload['last_name']    ?? '');
        $displayName = trim("{$firstName} {$lastName}");

        // ── Step 0: Duplicate name check ─────────────────────────
        $this->engine->logEvent($workflow, 'info', "Checking for duplicate display name: {$displayName}");
        $existingUser = IdentityUser::where('display_name', $displayName)->first();
        if ($existingUser) {
            throw new \RuntimeException(
                "User '{$displayName}' already exists in Azure (UPN: {$existingUser->user_principal_name})."
            );
        }

        // ── Step 1: Build UPN ─────────────────────────────────────
        // Use domain chosen on the create-user form (payload['upn_domain']),
        // then fall back to the global default, then a hard-coded placeholder.
        $domain = trim($payload['upn_domain'] ?? $settings->upn_domain ?? 'example.com') ?: 'example.com';
        $upn    = $this->buildUPN($firstName, $lastName, $domain);
        $this->engine->logEvent($workflow, 'info', "Generated UPN: {$upn}");

        // ── Step 2: Create Azure user ─────────────────────────────
        $this->engine->logEvent($workflow, 'info', 'Creating Azure AD user...');
        $graph    = new GraphService();
        $password = $payload['initial_password'] ?? (Str::random(12) . '!1A');

        try {
            $azureUser = $graph->createUser([
                'displayName'       => $displayName,
                'userPrincipalName' => $upn,
                'mailNickname'      => explode('@', $upn)[0],
                'password'          => $password,
                'usageLocation'     => 'EG',
                'jobTitle'          => $payload['job_title']   ?? null,
                'department'        => $payload['department']  ?? null,
                'accountEnabled'    => true,
            ]);

            $azureId = $azureUser['id'] ?? null;
            if (! $azureId) {
                throw new \RuntimeException('Azure user created but no ID returned.');
            }

            $this->engine->logEvent($workflow, 'success', "Azure user created: {$upn} (ID: {$azureId})");

            $payload = array_merge($payload, [
                'upn'          => $upn,
                'azure_id'     => $azureId,
                'display_name' => $displayName,
            ]);
            $workflow->payload = $payload;
            $workflow->save();

        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to create Azure user: ' . $e->getMessage());
        }

        // ── Step 3: Assign default license(s) ────────────────────
        // Priority: multi-sku array → legacy single sku → payload override
        $licenseSkus = $settings->graph_default_license_skus ?? [];
        if (empty($licenseSkus) && $settings->graph_default_license_sku) {
            $licenseSkus = [$settings->graph_default_license_sku];
        }
        // Allow payload to override (future: per-request license selection)
        if (!empty($payload['license_skus'])) {
            $licenseSkus = (array) $payload['license_skus'];
        } elseif (!empty($payload['license_sku']) && empty($licenseSkus)) {
            $licenseSkus = [$payload['license_sku']];
        }

        if (!empty($licenseSkus)) {
            foreach (array_filter($licenseSkus) as $sku) {
                $this->engine->logEvent($workflow, 'info', "Assigning license SKU: {$sku}");
                try {
                    $graph->assignLicense($azureId, $sku);
                    $this->engine->logEvent($workflow, 'success', "License {$sku} assigned.");
                } catch (\Throwable $e) {
                    $this->engine->logEvent($workflow, 'warning', "License {$sku} assignment failed (non-fatal): " . $e->getMessage());
                }
            }
        }

        // ── Step 4: Create UCM extension (branch-aware) ──────────
        // Branch UCM + range take priority; global settings are the fallback.
        $extension = null;
        $ucmServer = null;
        $branch    = $workflow->branch_id ? Branch::find($workflow->branch_id) : null;

        if ($branch) {
            $ucmServer = $branch->effectiveUcmServer($settings);
            $extRange  = $branch->effectiveExtRange($settings);
        } else {
            $ucmServer = $settings->default_ucm_id ? UcmServer::find($settings->default_ucm_id) : null;
            $extRange  = [
                'start' => (int) ($settings->ext_range_start ?? 1000),
                'end'   => (int) ($settings->ext_range_end   ?? 1999),
            ];
        }

        if ($ucmServer) {
            try {
                $rangeStart = $extRange['start'];
                $rangeEnd   = $extRange['end'];

                $this->engine->logEvent($workflow, 'info', "Finding available extension ({$rangeStart}–{$rangeEnd}) on UCM: {$ucmServer->name}");

                $extension = $this->extProvisioning->getFirstAvailable($ucmServer, $rangeStart, $rangeEnd);
                $this->engine->logEvent($workflow, 'info', "Using extension: {$extension}");

                $this->extProvisioning->createForUser($ucmServer, $extension, $displayName, $upn);
                $this->engine->logEvent($workflow, 'success', "UCM extension {$extension} created (voicemail=no, call_waiting=no).");

                $payload = array_merge($payload, [
                    'extension'     => $extension,
                    'ucm_server_id' => $ucmServer->id,
                ]);
                $workflow->payload = $payload;
                $workflow->save();

            } catch (\Throwable $e) {
                $this->engine->logEvent($workflow, 'warning', 'UCM extension creation failed (non-fatal): ' . $e->getMessage());
            }
        }

        // ── Step 5: Update Azure profile with templates (branch-aware) ──
        // Branch templates override global settings.
        $officeTemplate = $branch ? $branch->effectiveOfficeTemplate($settings) : $settings->profile_office_template;
        $phoneTemplate  = $branch ? $branch->effectivePhoneTemplate($settings)  : $settings->profile_phone_template;

        if ($branch && ($officeTemplate || $phoneTemplate)) {
            try {
                $this->engine->logEvent($workflow, 'info', 'Updating Azure profile with templates...');

                $profileFields = $this->extProvisioning->buildProfileFields(
                    $branch,
                    $extension ?? '',
                    $firstName,
                    $lastName,
                    $upn,
                    [
                        'officeLocation' => $officeTemplate,
                        'phone'          => $phoneTemplate,
                    ]
                );

                $updateData = [];
                if (! empty($profileFields['officeLocation'])) {
                    $updateData['officeLocation'] = $profileFields['officeLocation'];
                }
                if (! empty($profileFields['phone'])) {
                    $updateData['businessPhones'] = [$profileFields['phone']];
                }

                if (! empty($updateData)) {
                    $graph->updateUser($azureId, $updateData);
                    $this->engine->logEvent($workflow, 'success', 'Azure profile updated with office/phone templates.');
                }
            } catch (\Throwable $e) {
                $this->engine->logEvent($workflow, 'warning', 'Azure profile update failed (non-fatal): ' . $e->getMessage());
            }
        }

        // ── Step 6: Create employee record ────────────────────────
        $this->engine->logEvent($workflow, 'info', 'Creating employee record...');
        try {
            $employee = Employee::create([
                'azure_id'         => $azureId,
                'name'             => $displayName,
                'email'            => $upn,
                'branch_id'        => $workflow->branch_id,
                'department_id'    => $payload['department_id'] ?? null,
                'job_title'        => $payload['job_title']     ?? null,
                'status'           => 'active',
                'hired_date'       => now()->toDateString(),
                'extension_number' => $extension,
                'ucm_server_id'    => $ucmServer?->id,
            ]);
            $this->engine->logEvent($workflow, 'success', 'Employee record created.');

            // Save employee ID to payload so the show page can link to the profile
            $payload = array_merge($payload, ['employee_id' => $employee->id]);
            $workflow->payload = $payload;
            $workflow->save();
        } catch (\Throwable $e) {
            $this->engine->logEvent($workflow, 'warning', 'Employee record creation failed (non-fatal): ' . $e->getMessage());
        }

        // ── Step 7: Notify admins ─────────────────────────────────
        $extInfo = $extension ? " Extension: {$extension}." : '';
        $this->notifications->notifyAdmins(
            'workflow_complete',
            'User Provisioned',
            "New user '{$displayName}' ({$upn}) has been successfully provisioned.{$extInfo}",
            route('admin.workflows.show', $workflow->id),
            'info'
        );

        $this->engine->logEvent($workflow, 'success', 'User provisioning complete.');
    }

    // ─────────────────────────────────────────────────────────────
    // Build UPN: first.last@domain (with collision suffix)
    // ─────────────────────────────────────────────────────────────

    private function buildUPN(string $firstName, string $lastName, string $domain): string
    {
        $sanitize = function (string $s): string {
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
            $s = strtolower($s);
            $s = preg_replace('/[^a-z0-9]/', '', $s);
            return $s;
        };

        $first = $sanitize($firstName);
        $last  = $sanitize($lastName);
        $base  = $first . '.' . $last;

        $upn = "{$base}@{$domain}";
        if (! IdentityUser::where('user_principal_name', $upn)->exists()) {
            return $upn;
        }

        for ($i = 2; $i <= 99; $i++) {
            $candidate = "{$base}{$i}@{$domain}";
            if (! IdentityUser::where('user_principal_name', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException("No available UPN for {$firstName} {$lastName}@{$domain}");
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
            $this->engine->logEvent($workflow, 'info', 'Disabling Azure user...');
            try {
                $graph = new GraphService();
                $graph->disableUser($azureId);
                $this->engine->logEvent($workflow, 'success', 'Azure user disabled.');
            } catch (\Throwable $e) {
                throw new \RuntimeException('Failed to disable Azure user: ' . $e->getMessage());
            }

            $employee = Employee::where('azure_id', $azureId)->first();
            if ($employee) {
                $employee->update([
                    'status'          => 'terminated',
                    'terminated_date' => now()->toDateString(),
                ]);
                $this->engine->logEvent($workflow, 'success', 'Employee record updated to terminated.');
                $employee->activeAssets()->update(['notes' => 'PENDING RETURN — employee terminated']);
                $this->engine->logEvent($workflow, 'info', 'Active asset assignments flagged for return.');
            }
        }

        $this->engine->logEvent($workflow, 'success', 'User deprovisioning complete.');
    }
}
