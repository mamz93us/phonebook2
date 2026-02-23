<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GdmsService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected int $orgId;
    protected string $username;
    protected string $passwordHash;

    public function __construct()
    {
        $this->baseUrl      = rtrim(config('services.gdms.base_url', 'https://www.gdms.cloud/oapi'), '/');
        $this->clientId     = (string) config('services.gdms.client_id');
        $this->clientSecret = (string) config('services.gdms.client_secret');
        $this->orgId        = (int) config('services.gdms.org_id');

        // From .env
        $this->username     = (string) env('GDMS_USERNAME');       // GDMS login username
        $this->passwordHash = (string) env('GDMS_PASSWORD_HASH');  // sha256(md5(password))
    }

    /**
     * Obtain access token using password grant (same as in Postman).
     */
    protected function getToken(): string
    {
        $response = Http::asForm()->get("{$this->baseUrl}/oauth/token", [
            'username'      => $this->username,
            'password'      => $this->passwordHash,
            'grant_type'    => 'password',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $data = $response->json();

        if (!isset($data['access_token'])) {
            throw new \RuntimeException('GDMS token error: '.($data['error_description'] ?? 'unknown'));
        }

        return $data['access_token'];
    }

    /**
     * List SIP accounts (v1.0.0) with same pattern as your working Postman request.
     */
    public function listSipAccounts(int $pageNum = 1, int $pageSize = 1000): array
    {
        $token     = $this->getToken();

        // Use current time in ms; if you get timestamp errors, you can switch to serverTimestamp
        $timestamp = (string) round(microtime(true) * 1000);
        $orgId     = $this->orgId;

        // Body JSON – must match exactly what we sign and send
        $bodyArray = [
            'pageNum'  => $pageNum,
            'pageSize' => $pageSize,
            'orgId'    => $orgId,
        ];
        $bodyJson = json_encode($bodyArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Signature parameters (query + body fields)
        $sigParams = [
            'access_token'  => $token,
            'orgId'         => $orgId,
            'pageNum'       => $pageNum,
            'pageSize'      => $pageSize,
            'timestamp'     => $timestamp,
        ];

        // Add client_id and client_secret only for signature
        $sigParams['client_id']     = $this->clientId;
        $sigParams['client_secret'] = $this->clientSecret;

        // Sort keys ASC
        ksort($sigParams, SORT_STRING);

        $pairs = [];
        foreach ($sigParams as $key => $value) {
            $pairs[] = $key.'='.$value;
        }
        $paramString = implode('&', $pairs);

        // sha256(body)
        $bodyHash = hash('sha256', $bodyJson);

        // Final string: &params&sha256(body)&
        $toSign = '&'.$paramString.'&'.$bodyHash.'&';

        $signature = hash('sha256', $toSign);

        // Build URL exactly like your working Postman call
        $url = "{$this->baseUrl}/v1.0.0/sip/account/list"
             . "?access_token={$token}"
             . "&timestamp={$timestamp}"
             . "&signature={$signature}"
             . "&pageSize={$pageSize}"
             . "&pageNum={$pageNum}"
             . "&orgId={$orgId}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $bodyArray);

        $data = $response->json();

        if (($data['retCode'] ?? -1) !== 0) {
            throw new \RuntimeException('GDMS error: '.($data['msg'] ?? 'unknown'));
        }

        return $data['data'] ?? [];
    }

    /**
     * List all devices in GDMS, optionally filtering by productName prefix.
     * e.g. listDevices(productName: 'UCM') returns only UCM devices.
     * Each item includes: mac, deviceName, productName, firmwareVersion, deviceIp, online (1/0)
     */
    public function listDevices(int $pageNum = 1, int $pageSize = 1000, ?string $productName = null): array
    {
        $token     = $this->getToken();
        $timestamp = (string) round(microtime(true) * 1000);
        $orgId     = $this->orgId;

        $bodyArray = [
            'pageNum'  => $pageNum,
            'pageSize' => $pageSize,
            'orgId'    => $orgId,
        ];

        $bodyJson = json_encode($bodyArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sigParams = [
            'access_token'  => $token,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'orgId'         => $orgId,
            'pageNum'       => $pageNum,
            'pageSize'      => $pageSize,
            'timestamp'     => $timestamp,
        ];

        ksort($sigParams, SORT_STRING);

        $pairs = [];
        foreach ($sigParams as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        $toSign    = '&' . implode('&', $pairs) . '&' . hash('sha256', $bodyJson) . '&';
        $signature = hash('sha256', $toSign);

        $url = "{$this->baseUrl}/v1.0.0/device/list"
             . "?access_token={$token}"
             . "&timestamp={$timestamp}"
             . "&signature={$signature}"
             . "&pageSize={$pageSize}"
             . "&pageNum={$pageNum}"
             . "&orgId={$orgId}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $bodyArray);

        $data = $response->json();

        if (($data['retCode'] ?? -1) !== 0) {
            throw new \RuntimeException('GDMS listDevices error: ' . ($data['msg'] ?? 'unknown'));
        }

        $list = $data['data']['dataList'] ?? [];

        // Optional client-side filter by productName prefix
        if ($productName) {
            $prefix = strtoupper($productName);
            $list   = array_values(array_filter($list, function ($device) use ($prefix) {
                return str_starts_with(strtoupper($device['productName'] ?? ''), $prefix);
            }));
        }

        return $list;
    }

    /**
     * Fetch SIP accounts for a device via the device/detail polling API.
     *
     * Mirrors the PHP script pattern: trigger with isFirst=1, then poll
     * with isFirst=0 until accounts arrive (up to 20 × 3 s = 60 s).
     *
     * @param  string $rawMac  MAC in any format (ec74d7800474 or EC:74:D7:80:04:74)
     * @return array|null      sipAccountList array, or null if device unreachable
     */
    public function getDeviceAccounts(string $rawMac): ?array
    {
        $token = $this->getToken();
        $mac   = $this->formatMacForApi($rawMac); // → EC:74:D7:80:04:74

        // Step 1: trigger device to push its data
        $this->callDeviceDetail($token, $mac, 1);

        // Step 2: poll until accounts appear
        for ($i = 0; $i < 20; $i++) {
            sleep(3);
            $res     = $this->callDeviceDetail($token, $mac, 0);
            $retCode = $res['retCode'] ?? -1;
            $sipList = $res['data']['sipAccountList']
                    ?? $res['data']['fxsPortList']
                    ?? [];

            if ($retCode === 0 && ! empty($sipList)) {
                return $sipList;
            }

            if ($retCode !== 0) {
                break; // API error – stop polling
            }
            // retCode=0 but empty → device hasn't responded yet, keep polling
        }

        return null;
    }

    /**
     * Call /v1.0.0/device/detail with the signature pattern from the PHP reference script.
     * Signature covers: access_token, client_id, client_secret, timestamp (no orgId).
     */
    private function callDeviceDetail(string $token, string $mac, int $isFirst): array
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $bodyJson  = json_encode(
            ['mac' => $mac, 'isFirst' => (string) $isFirst],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $sigParams = [
            'access_token'  => $token,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'timestamp'     => $timestamp,
        ];

        ksort($sigParams, SORT_STRING);

        $pairs = [];
        foreach ($sigParams as $k => $v) {
            $pairs[] = "$k=$v";
        }

        $toSign    = '&' . implode('&', $pairs) . '&' . hash('sha256', $bodyJson) . '&';
        $signature = hash('sha256', $toSign);

        $url = "{$this->baseUrl}/v1.0.0/device/detail"
             . '?access_token=' . urlencode($token)
             . "&timestamp={$timestamp}"
             . "&signature={$signature}";

        $response = Http::withOptions(['verify' => false])
            ->withBody($bodyJson, 'application/json')
            ->post($url);

        return $response->json() ?? [];
    }

    /**
     * Normalize any MAC format to colon-separated uppercase.
     * ec74d7800474  →  EC:74:D7:80:04:74
     */
    private function formatMacForApi(string $mac): string
    {
        $hex = strtoupper(preg_replace('/[^0-9a-fA-F]/', '', $mac));

        return implode(':', str_split($hex, 2));
    }
}
