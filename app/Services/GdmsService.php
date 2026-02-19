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
}
