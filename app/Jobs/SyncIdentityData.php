<?php

namespace App\Jobs;

use App\Models\IdentityGroup;
use App\Models\IdentityLicense;
use App\Models\IdentitySyncLog;
use App\Models\IdentityUser;
use App\Models\Setting;
use App\Services\Identity\GraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncIdentityData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function handle(): void
    {
        $settings = Setting::get();

        if (!$settings->identity_sync_enabled) {
            Log::info('SyncIdentityData: disabled — skipping.');
            return;
        }

        if (empty($settings->graph_tenant_id) || empty($settings->graph_client_id) || empty($settings->graph_client_secret)) {
            Log::warning('SyncIdentityData: Graph credentials not configured — skipping.');
            return;
        }

        $log = IdentitySyncLog::create([
            'type'       => 'full',
            'status'     => 'started',
            'started_at' => now(),
        ]);

        try {
            $graph = new GraphService();

            // ── 1. Sync licenses ───────────────────────────────────────
            $skus = $graph->listSubscribedSkus();
            foreach ($skus as $sku) {
                IdentityLicense::updateOrCreate(
                    ['sku_id' => $sku['skuId']],
                    [
                        'sku_part_number'    => $sku['skuPartNumber'],
                        'display_name'       => $sku['skuPartNumber'], // Graph doesn't always return a friendly name
                        'total'              => $sku['prepaidUnits']['enabled'] ?? 0,
                        'consumed'           => $sku['consumedUnits'] ?? 0,
                        'available'          => max(0, ($sku['prepaidUnits']['enabled'] ?? 0) - ($sku['consumedUnits'] ?? 0)),
                        'applies_to'         => $sku['appliesTo'] ?? null,
                        'capability_status'  => $sku['capabilityStatus'] ?? 'Enabled',
                    ]
                );
            }

            // ── 2. Sync groups ─────────────────────────────────────────
            $groups = $graph->listGroups();
            foreach ($groups as $group) {
                IdentityGroup::updateOrCreate(
                    ['azure_id' => $group['id']],
                    [
                        'display_name'     => $group['displayName'],
                        'description'      => $group['description'] ?? null,
                        'group_type'       => in_array('Unified', $group['groupTypes'] ?? []) ? 'Unified' : null,
                        'mail_enabled'     => $group['mailEnabled'] ?? false,
                        'security_enabled' => $group['securityEnabled'] ?? true,
                    ]
                );
            }

            // ── 3. Sync users ──────────────────────────────────────────
            $users = $graph->listUsers();
            foreach ($users as $user) {
                $licensesCount = count($user['assignedLicenses'] ?? []);
                $licenseSkus   = collect($user['assignedLicenses'] ?? [])->pluck('skuId')->all();

                IdentityUser::updateOrCreate(
                    ['azure_id' => $user['id']],
                    [
                        'display_name'        => $user['displayName'],
                        'user_principal_name' => $user['userPrincipalName'],
                        'mail'                => $user['mail'] ?? null,
                        'job_title'           => $user['jobTitle'] ?? null,
                        'department'          => $user['department'] ?? null,
                        'account_enabled'     => $user['accountEnabled'] ?? true,
                        'usage_location'      => $user['usageLocation'] ?? null,
                        'licenses_count'      => $licensesCount,
                        'assigned_licenses'   => $licenseSkus,
                        'raw_data'            => $user,
                    ]
                );
            }

            $log->update([
                'status'          => 'completed',
                'users_synced'    => count($users),
                'licenses_synced' => count($skus),
                'groups_synced'   => count($groups),
                'completed_at'    => now(),
            ]);

            Log::info('SyncIdentityData: completed. Users: ' . count($users) . ', Licenses: ' . count($skus) . ', Groups: ' . count($groups));
        } catch (\Exception $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            Log::error('SyncIdentityData failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
