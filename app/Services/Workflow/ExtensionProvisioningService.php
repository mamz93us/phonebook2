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

        // UCM strictly requires an alphanumeric password WITH special characters
        $complexSecret = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6) . 
                         substr(str_shuffle('0123456789'), 0, 3) . 
                         substr(str_shuffle('!@#$%^&*_+?'), 0, 3);
        $complexSecret = str_shuffle($complexSecret);

        // 1. Create with minimal required fields (avoids error -25)
        $result = $api->createExtension([
            'extension'     => $extension,
            'secret'        => $complexSecret,
            'user_password' => $complexSecret, 
            'vmsecret'      => (string) random_int(100000, 999999),
            'permission'    => $settings->ext_default_permission ?: 'internal-local',
        ]);

        // 2. Update with user profile data and SIP options
        try {
            $api->updateExtension($extension, [
                'fullname'     => $displayName,
                'email'        => $email,
                'hasvoicemail' => 'no',
                'call_waiting' => 'no',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("ExtensionProvisioningService: failed post-create update for {$extension}", [
                'error' => $e->getMessage()
            ]);
        }

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
