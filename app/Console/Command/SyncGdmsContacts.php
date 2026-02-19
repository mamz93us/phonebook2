<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GdmsService;
use App\Services\GdmsBranchMapper;
use App\Models\Contact;

class SyncGdmsContacts extends Command
{
    protected $signature = 'gdms:sync-contacts';
    protected $description = 'Sync SIP accounts from GDMS into contacts table';

    public function handle(GdmsService $gdms, GdmsBranchMapper $branchMapper)
    {
        $pageNum  = 1;
        $pageSize = 1000;

        $this->info('Fetching SIP accounts from GDMS...');

        $data = $gdms->listSipAccounts($pageNum, $pageSize);

        $total  = $data['total']  ?? 0;
        $pages  = $data['pages']  ?? 1;
        $result = $data['result'] ?? [];

        $this->info("Total accounts: {$total}, pages: {$pages}");

        $processed = 0;

        for ($p = 1; $p <= $pages; $p++) {
            if ($p > 1) {
                $data   = $gdms->listSipAccounts($p, $pageSize);
                $result = $data['result'] ?? [];
            }

            foreach ($result as $acc) {
                $processed++;

                $sipUserId      = $acc['sipUserId'] ?? null;      // extension
                $accountName    = $acc['accountName'] ?? null;
                $displayName    = $acc['displayName'] ?? null;
                $sipServer      = $acc['sipServer'] ?? '';
                $extensionEmail = $acc['extensionEmail'] ?? null; // if available in your version

                if (! $sipUserId) {
                    continue;
                }

                $fullName = $accountName ?: ($displayName ?: $sipUserId);
                [$first, $last] = $this->splitName($fullName);

                $branchId = $branchMapper->resolveBranchId($sipServer);

                Contact::updateOrCreate(
                    ['phone' => $sipUserId],
                    [
                        'first_name' => $first,
                        'last_name'  => $last,
                        'phone'      => $sipUserId,
                        'email'      => $extensionEmail,
                        'branch_id'  => $branchId,
                    ]
                );
            }

            $this->info("Page {$p} processed ({$processed} accounts so far)");
        }

        $this->info('GDMS contacts sync complete.');
        return Command::SUCCESS;
    }

    protected function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return ['', ''];
        }
        $parts = explode(' ', $name, 2);
        return [$parts[0], $parts[1] ?? ''];
    }
}
