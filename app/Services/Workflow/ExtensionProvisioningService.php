<?php

namespace App\Services\Workflow;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\UcmServer;
use App\Services\IppbxApiService;

class ExtensionProvisioningService
{
    /**
     * Get the first available extension number in the range [start..end].
     * Queries the UCM API to get all used extensions, then returns the first gap.
     */
    public function getFirstAvailable(UcmServer $server, int $start, int $end): string
    {
        $api = new IppbxApiService($server);
        $api->login();

        $extensions  = $api->listExtensions();
        $usedNumbers = collect($extensions)
            ->pluck('extension')
            ->map(fn ($e) => (int) $e)
            ->toArray();

        for ($n = $start; $n <= $end; $n++) {
            if (! in_array($n, $usedNumbers)) {
                return (string) $n;
            }
        }

        throw new \RuntimeException("Extension range {$start}–{$end} is fully exhausted.");
    }

    /**
     * Create a UCM extension for a new user.
     * Voicemail and call_waiting are always disabled.
     * Secret and permission come from settings.
     */
    public function createForUser(
        UcmServer $server,
        string    $extension,
        string    $displayName,
        string    $email
    ): array {
        $settings = Setting::get();

        $api = new IppbxApiService($server);
        $api->login();

        $defaultSecret = $settings->ext_default_secret ?: 'changeme1@3';

        $result = $api->createExtension([
            'extension'     => $extension,
            'fullname'      => $displayName,
            'email'         => $email,
            'hasvoicemail'  => 'no',
            'call_waiting'  => 'no',
            // UCM password complexity policy requires uppercase+lowercase+digit+special char.
            // Do NOT strip special characters — they are required, not forbidden.
            'secret'        => $defaultSecret,
            'user_password' => $defaultSecret, // Required by addSIPAccountAndUser
            'vmsecret'      => $defaultSecret, // Required by addSIPAccountAndUser
            // UCM permission values must be the full cumulative strings (per API docs)
            'permission'    => $settings->ext_default_permission ?: 'internal-local',
        ]);

        $api->applyChanges();

        return $result;
    }

    /**
     * Replace template variables and build Azure profile fields.
     *
     * Supported variables: {branch_name}, {branch_phone}, {extension},
     *                       {first_name}, {last_name}, {upn}
     *
     * @param  array<string, string|null> $templates  ['officeLocation' => '...', 'phone' => '...']
     * @return array<string, string>
     */
    public function buildProfileFields(
        Branch $branch,
        string $extension,
        string $firstName,
        string $lastName,
        string $upn,
        array  $templates
    ): array {
        $vars = [
            '{branch_name}'  => $branch->name          ?? '',
            '{branch_phone}' => $branch->phone_number  ?? '',
            '{extension}'    => $extension,
            '{first_name}'   => $firstName,
            '{last_name}'    => $lastName,
            '{upn}'          => $upn,
        ];

        $result = [];
        foreach ($templates as $key => $template) {
            if ($template) {
                $result[$key] = str_replace(array_keys($vars), array_values($vars), $template);
            }
        }

        return $result;
    }
}
