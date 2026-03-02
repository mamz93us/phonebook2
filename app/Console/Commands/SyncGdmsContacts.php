<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\ActivityLog; // your existing activity log model
use App\Services\GdmsService;
use App\Services\GdmsBranchMapper;
use Illuminate\Console\Command;
       use App\Models\User; // at top if you want to link to a user

class SyncGdmsContacts extends Command
{
    protected $signature = 'gdms:sync-contacts';
    protected $description = 'Sync contacts from GDMS SIP accounts into local contacts table';

    public function handle(GdmsService $gdms, GdmsBranchMapper $branchMapper): int
    {
        $this->info('Fetching SIP accounts from GDMS...');

        $pageNum   = 1;
        $pageSize  = 200;
        $processed = 0;

        do {
            $pageData = $gdms->listSipAccounts($pageNum, $pageSize);
            $accounts = $pageData['list'] ?? [];
            $total    = $pageData['total'] ?? count($accounts);

            if ($pageNum === 1) {
                $pages = (int) ceil($total / $pageSize);
                $this->info("Total accounts: {$total}, pages: {$pages}");
            }

            foreach ($accounts as $acc) {
                $sipUserId    = $acc['sipUserId'] ?? null;
                $displayName  = $acc['displayName'] ?? '';
                $sipServer    = $acc['sipServer'] ?? '';

                if (!$sipUserId) {
                    continue;
                }

                $branchId = $branchMapper->resolveBranchId($sipServer);

                $parts     = preg_split('/\s+/', trim($displayName));
                $firstName = $parts[0] ?? (string) $sipUserId;
                $lastName  = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';

                Contact::updateOrCreate(
                    ['phone' => $sipUserId],
                    [
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'email'      => null,
                        'branch_id'  => $branchId,
                    ]
                );

                $processed++;
            }

            $pageNum++;
        } while (!empty($accounts) && isset($total) && $processed < $total);

        $this->info("GDMS contacts sync complete. Processed {$processed} accounts.");

        // Activity log entry – adjust fields to match your table

// ...

if (class_exists(\App\Models\ActivityLog::class)) {
    \App\Models\ActivityLog::create([
        'model_type' => User::class,                 // or 'system' if you prefer
        'model_id'   => 1,                           // some existing user id, or 0
        'action'     => 'gdms_sync_contacts',
        'changes'    => json_encode([
            'processed' => $processed,
            'source'    => 'gdms',
            'run_from'  => 'cli',
        ]),
        'user_id'    => 1,                           // same system user id
    ]);
}


        return Command::SUCCESS;
    }
}
