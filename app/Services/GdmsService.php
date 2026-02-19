<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GdmsService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $username;
    protected string $passwordHash;
    protected int $orgId;

    public function __construct()
    {
        $this->baseUrl      = rtrim(config('services.gdms.base_url'), '/');
        $this->clientId     = config('services.gdms.client_id');
        $this->clientSecret = config('services.gdms.client_secret');
        $this->orgId        = (int) config('services.gdms.org_id');

        // Optional: move username/passwordHash to env if you want to auto-get token
        $this->username     = env('GDMS_USERNAME');
        $this->passwordHash = env('GDMS_PASSWORD_HASH'); // sha256(md5(password))
    }

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

        return $data['access_token'];
    }

    protected function serverTimestamp(): string
    {
        // You already saw serverTimestamp in error; you can call any endpoint once
        // or just use current time in ms. Here we use local time:
        return (string) round(microtime(true) * 1000);
    }

    protected function buildSignature(array $params, string $body = null): string
    {
        // Add client_id and client_secret for signature only
        $params['client_id']     = $this->clientId;
        $params['client_secret'] = $this->clientSecret;

        ksort($params, SORT_STRING);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key.'='.$value;
        }
        $paramString = implode('&', $pairs);

        if ($body !== null) {
            $bodyHash = hash('sha256', $body);
            $toSign   = '&'.$paramString.'&'.$bodyHash.'&';
        } else {
            $toSign   = '&'.$paramString.'&';
        }

        return hash('sha256', $toSign);
    }

   public function listSipAccounts(int $pageNum = 1, int $pageSize = 1000): array
{
    $token     = $this->getToken();
    $timestamp = $this->serverTimestamp();

    $bodyArray = [
        'pageNum'  => $pageNum,
        'pageSize' => $pageSize,
        'orgId'    => $this->orgId,
    ];
    $body = json_encode($bodyArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Params used in signature
    $sigParams = [
        'access_token' => $token,
        'orgId'        => $this->orgId,
        'pageNum'      => $pageNum,
        'pageSize'     => $pageSize,
        'timestamp'    => $timestamp,
    ];

    $signature = $this->buildSignature($sigParams, $body);

    $url = "{$this->baseUrl}/v1.0.0/sip/account/list"
         . "?access_token={$token}"
         . "&timestamp={$timestamp}"
         . "&signature={$signature}";

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
