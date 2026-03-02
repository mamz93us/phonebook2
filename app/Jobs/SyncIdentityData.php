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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncIdentityData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

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
            DB::transaction(function () use ($skus) {
                foreach ($skus as $sku) {
                    IdentityLicense::updateOrCreate(
                        ['sku_id' => $sku['skuId']],
                        [
                            'sku_part_number'   => $sku['skuPartNumber'],
                            'display_name'      => $sku['skuPartNumber'],
                            'total'             => $sku['prepaidUnits']['enabled'] ?? 0,
                            'consumed'          => $sku['consumedUnits'] ?? 0,
                            'available'         => max(0, ($sku['prepaidUnits']['enabled'] ?? 0) - ($sku['consumedUnits'] ?? 0)),
                            'applies_to'        => $sku['appliesTo'] ?? null,
                            'capability_status' => $sku['capabilityStatus'] ?? 'Enabled',
                        ]
                    );
                }
            });

            // ── 2. Sync groups ─────────────────────────────────────────
            $groups = $graph->listGroups();
            DB::transaction(function () use ($groups) {
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
            });

            // ── 3. Sync user profile data (no expand — stays fast) ─────
            $users = $graph->listUsers();
            DB::transaction(function () use ($users) {
                foreach ($users as $user) {
                    $licenseSkus = collect($user['assignedLicenses'] ?? [])->pluck('skuId')->all();

                    IdentityUser::updateOrCreate(
                        ['azure_id' => $user['id']],
                        [
                            'manager_azure_id'    => $user['manager_id'] ?? null,
                            'display_name'        => $user['displayName'],
                            'user_principal_name' => $user['userPrincipalName'],
                            'mail'                => $user['mail'] ?? null,
                            'job_title'           => $user['jobTitle'] ?? null,
                            'department'          => $user['department'] ?? null,
                            'company_name'        => $user['companyName'] ?? null,
                            'account_enabled'     => $user['accountEnabled'] ?? true,
                            'usage_location'      => $user['usageLocation'] ?? null,
                            'phone_number'        => $user['businessPhones'][0] ?? null,
                            'mobile_phone'        => $user['mobilePhone'] ?? null,
                            'office_location'     => $user['officeLocation'] ?? null,
                            'street_address'      => $user['streetAddress'] ?? null,
                            'city'                => $user['city'] ?? null,
                            'postal_code'         => $user['postalCode'] ?? null,
                            'country'             => $user['country'] ?? null,
                            'licenses_count'      => count($licenseSkus),
                            'assigned_licenses'   => $licenseSkus,
                            'raw_data'            => $user,
                        ]
                    );
                }
            });

            // ── 4. Sync group memberships via Graph Batch API ──────────
            // batchGroupMembers() sends 20 group-member requests per HTTP
            // call (parallel on Microsoft's side) — much faster than
            // paginating users with $expand=memberOf.
            $allGroupIds = IdentityGroup::pluck('azure_id')->all();
            $groupMembers = $graph->batchGroupMembers($allGroupIds);
            // $groupMembers = [groupId => [userId, ...]]

            // Build inverse map: userId → [groupId, ...]
            $userMemberOf      = [];
            $groupMemberCounts = [];
            foreach ($groupMembers as $groupId => $userIds) {
                $groupMemberCounts[$groupId] = count($userIds);
                foreach ($userIds as $uid) {
                    $userMemberOf[$uid][] = $groupId;
                }
            }

            // Persist user membership data in one transaction
            DB::transaction(function () use ($userMemberOf) {
                foreach ($userMemberOf as $userId => $groupIds) {
                    IdentityUser::where('azure_id', $userId)->update([
                        'member_of'    => $groupIds,   // cast: 'array' handles JSON
                        'groups_count' => count($groupIds),
                    ]);
                }
            });

            // ── 5. Back-fill members_count on groups ───────────────────
            DB::transaction(function () use ($groupMemberCounts, $allGroupIds) {
                foreach ($groupMemberCounts as $gid => $count) {
                    IdentityGroup::where('azure_id', $gid)->update(['members_count' => $count]);
                }
                // Zero out groups with no members this run
                IdentityGroup::whereNotIn('azure_id', array_keys($groupMemberCounts))
                    ->update(['members_count' => 0]);
            });

            $log->update([
                'status'          => 'completed',
                'users_synced'    => count($users),
                'licenses_synced' => count($skus),
                'groups_synced'   => count($groups),
                'completed_at'    => now(),
            ]);

            Log::info("SyncIdentityData: completed. Users: " . count($users) . ", Licenses: " . count($skus) . ", Groups: " . count($groups));

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
