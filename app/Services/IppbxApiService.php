<?php

namespace App\Services;

use App\Models\UcmServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IppbxApiService
{
    protected string $baseUrl;
    protected string $originUrl;   // base URL without /api — used for headers
    protected string $username;
    protected string $password;
    protected ?string $cookie      = null;
    protected ?string $cloudDomain = null;  // GDMS cloud relay override for Wave QR

    public function __construct(UcmServer $server)
    {
        $this->originUrl   = rtrim($server->url, '/');
        $this->baseUrl     = $this->originUrl . '/api';
        $this->username    = $server->api_username;
        $this->password    = $server->api_password;
        $this->cloudDomain = $server->cloud_domain ?: null;
    }

    // ─────────────────────────────────────────────
    // Authentication
    // ─────────────────────────────────────────────

    /**
     * Full login flow: challenge → MD5 token → login → return cookie
     */
    public function login(): string
    {
        // Step 1: get challenge
        $challengeResp = $this->post([
            'action'  => 'challenge',
            'user'    => $this->username,
            'version' => '1.0',
        ]);

        if (!isset($challengeResp['response']['challenge'])) {
            throw new \RuntimeException('UCM challenge failed: ' . json_encode($challengeResp));
        }

        $challenge = $challengeResp['response']['challenge'];

        // Step 2: MD5(challenge + password)
        $token = md5($challenge . $this->password);

        // Step 3: login
        $loginResp = $this->post([
            'action' => 'login',
            'user'   => $this->username,
            'token'  => $token,
        ]);

        if (!isset($loginResp['response']['cookie'])) {
            throw new \RuntimeException('UCM login failed: ' . json_encode($loginResp));
        }

        $this->cookie = $loginResp['response']['cookie'];
        return $this->cookie;
    }

    /**
     * Apply pending changes to the UCM.
     * Retries once with a fresh login if the cookie expired (status -6).
     */
    public function applyChanges(): array
    {
        // applyChanges can take longer — use 30s timeout
        $resp = $this->post([
            'action' => 'applyChanges',
            'cookie' => $this->cookie,
        ], 30);

        // -6 = invalid/expired cookie → re-login and retry once
        if (($resp['status'] ?? null) === -6) {
            Log::warning('IppbxApiService: applyChanges got -6 (expired cookie), re-logging in.');
            $this->cookie = null;
            $this->login();

            $resp = $this->post([
                'action' => 'applyChanges',
                'cookie' => $this->cookie,
            ], 30);
        }

        if (($resp['status'] ?? -1) !== 0) {
            Log::error('IppbxApiService: applyChanges failed', ['response' => $resp]);
            throw new \RuntimeException('applyChanges failed: ' . json_encode($resp));
        }

        Log::info('IppbxApiService: applyChanges OK');
        return $resp;
    }

    // ─────────────────────────────────────────────
    // Extensions
    // ─────────────────────────────────────────────

    /**
     * List all extensions
     */
    public function listExtensions(int $page = 1, int $itemNum = 500): array
    {
        $this->ensureCookie();

        $resp = $this->post([
            'action'   => 'listAccount',
            'cookie'   => $this->cookie,
            'options'  => 'extension,account_type,fullname,status,addr',
            'page'     => (string) $page,
            'item_num' => (string) $itemNum,
            'sidx'     => 'extension',
            'sord'     => 'asc',
        ]);

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('listAccount failed: ' . json_encode($resp));
        }

        return $resp['response']['account'] ?? [];
    }

    /**
     * Get a single extension details
     */
    public function getExtension(string $extension): array
    {
        $this->ensureCookie();

        $resp = $this->post([
            'action'    => 'getSIPAccount',
            'cookie'    => $this->cookie,
            'extension' => $extension,
        ]);

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('getSIPAccount failed: ' . json_encode($resp));
        }

        return $resp['response']['extension'] ?? [];
    }

    /**
     * Get extension details for Wave / SIP client provisioning.
     * Returns extension, fullname, secret, email, and the UCM SIP domain.
     */
    public function getExtensionWave(string $extension): array
    {
        $details = $this->getExtension($extension);

        // Use the GDMS cloud domain if configured, otherwise fall back to UCM URL host
        $host = $this->cloudDomain
            ?? (parse_url($this->originUrl, PHP_URL_HOST) ?? $this->originUrl);

        return [
            'extension'    => $details['extension'] ?? $extension,
            'fullname'     => $details['fullname']  ?? '',
            'secret'       => $details['secret']    ?? '',
            'email'        => $details['email']     ?? '',
            'server'       => $host,
            'sip_uri'      => 'sip:' . ($details['extension'] ?? $extension) . '@' . $host,
            'cloud_domain' => $this->cloudDomain !== null,
        ];
    }

    /**
     * Create a new SIP extension
     */
    public function createExtension(array $data): array
    {
        $this->ensureCookie();

        $resp = $this->post(array_merge([
            'action' => 'addSIPAccountAndUser',
            'cookie' => $this->cookie,
        ], $data));

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('addSIPAccountAndUser failed: ' . json_encode($resp));
        }

        $this->applyChanges();
        return $resp;
    }

    /**
     * Update an existing SIP extension
     */
    public function updateExtension(string $extension, array $data): array
    {
        $this->ensureCookie();

        $resp = $this->post(array_merge([
            'action'    => 'updateSIPAccount',
            'cookie'    => $this->cookie,
            'extension' => $extension,
        ], $data));

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('updateSIPAccount failed: ' . json_encode($resp));
        }

        $this->applyChanges();
        return $resp;
    }

    /**
     * Delete an extension
     */
    public function deleteExtension(string $extension): array
    {
        $this->ensureCookie();

        $resp = $this->post([
            'action'    => 'deleteUser',
            'cookie'    => $this->cookie,
            'user_name' => $extension,
        ]);

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('deleteUser failed: ' . json_encode($resp));
        }

        $this->applyChanges();
        return $resp;
    }

    // ─────────────────────────────────────────────
    // System Status
    // ─────────────────────────────────────────────

    /**
     * Get system status (uptime, system time, serial number, part number)
     */
    public function getSystemStatus(): array
    {
        $this->ensureCookie();

        $resp = $this->post([
            'action' => 'getSystemStatus',
            'cookie' => $this->cookie,
        ]);

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('getSystemStatus failed: ' . json_encode($resp));
        }

        return $resp['response'] ?? [];
    }

    /**
     * Get system general status (firmware versions, product model)
     */
    public function getSystemGeneralStatus(): array
    {
        $this->ensureCookie();

        $resp = $this->post([
            'action' => 'getSystemGeneralStatus',
            'cookie' => $this->cookie,
        ]);

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('getSystemGeneralStatus failed: ' . json_encode($resp));
        }

        return $resp['response'] ?? [];
    }

    // ─────────────────────────────────────────────
    // VoIP Trunks
    // ─────────────────────────────────────────────

    /**
     * List all VoIP trunks
     */
    public function listVoIPTrunks(int $page = 1, int $itemNum = 500): array
    {
        $this->ensureCookie();

        $resp = $this->post([
            'action'   => 'listVoIPTrunk',
            'cookie'   => $this->cookie,
            'options'  => 'trunk_index,trunk_name,host,trunk_type,username,trunks.out_of_service,status',
            'page'     => (string) $page,
            'item_num' => (string) $itemNum,
            'sidx'     => 'trunk_index',
            'sord'     => 'asc',
        ]);

        if (($resp['status'] ?? -1) !== 0) {
            throw new \RuntimeException('listVoIPTrunk failed: ' . json_encode($resp));
        }

        return $resp['response']['voip_trunk'] ?? [];
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    protected function ensureCookie(): void
    {
        if (!$this->cookie) {
            $this->login();
        }
    }

    protected function post(array $payload, int $timeout = 15): array
    {
        try {
            $body = json_encode(['request' => $payload]);

            $response = Http::withoutVerifying()
                ->timeout($timeout)
                ->withHeaders([
                    'Content-Type'     => 'application/json;charset=UTF-8',
                    'Accept'           => 'application/json',
                    'Connection'       => 'close',
                    'Origin'           => $this->originUrl,
                    'Referer'          => $this->originUrl . '/',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->withBody($body, 'application/json')
                ->post($this->baseUrl);

            $json = $response->json();

            if ($json === null) {
                $httpStatus = $response->status();
                $bodyPreview = substr($response->body(), 0, 400);
                Log::error('IppbxApiService: non-JSON response', [
                    'url'    => $this->baseUrl,
                    'status' => $httpStatus,
                    'body'   => $bodyPreview,
                ]);
                throw new \RuntimeException(
                    "UCM returned HTTP {$httpStatus} (non-JSON). " .
                    "Response: " . ($bodyPreview ?: '(empty)')
                );
            }

            return $json;

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('IppbxApiService error: ' . $e->getMessage(), ['url' => $this->baseUrl]);
            throw new \RuntimeException(
                'UCM connection failed: ' . $e->getMessage()
            );
        }
    }
}
