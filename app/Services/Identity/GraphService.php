<?php

namespace App\Services\Identity;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphService
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl = 'https://graph.microsoft.com/v1.0';

    public function __construct(
        ?string $tenantId     = null,
        ?string $clientId     = null,
        ?string $clientSecret = null
    ) {
        $settings           = Setting::get();
        $this->tenantId     = $tenantId     ?? $settings->graph_tenant_id     ?? '';
        $this->clientId     = $clientId     ?? $settings->graph_client_id     ?? '';
        $this->clientSecret = $clientSecret ?? $settings->graph_client_secret ?? '';
    }

    // ─────────────────────────────────────────────────────────────
    // Authentication
    // ─────────────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        $cacheKey = "graph_token_{$this->clientId}";

        return Cache::remember($cacheKey, 3500, function () {
            $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

            $response = Http::asForm()->post($url, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]);

            if (!$response->successful()) {
                $err = $response->json('error_description') ?? $response->body();
                throw new \RuntimeException("Graph auth failed: {$err}");
            }

            return $response->json('access_token');
        });
    }

    private function get(string $endpoint, array $query = []): array
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)->get($this->baseUrl . $endpoint, $query);

        if ($response->status() === 401) {
            Cache::forget("graph_token_{$this->clientId}");
            $token    = $this->getAccessToken();
            $response = Http::withToken($token)->get($this->baseUrl . $endpoint, $query);
        }

        if (!$response->successful()) {
            throw new \RuntimeException("Graph GET {$endpoint} failed: " . $response->body());
        }

        return $response->json();
    }

    private function patch(string $endpoint, array $data): void
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)->patch($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            throw new \RuntimeException("Graph PATCH {$endpoint} failed: " . $response->body());
        }
    }

    private function post(string $endpoint, array $data): array
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)->post($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            throw new \RuntimeException("Graph POST {$endpoint} failed: " . $response->body());
        }

        return $response->json() ?? [];
    }

    private function delete(string $endpoint): void
    {
        $token    = $this->getAccessToken();
        $response = Http::withToken($token)->delete($this->baseUrl . $endpoint);

        if (!$response->successful()) {
            throw new \RuntimeException("Graph DELETE {$endpoint} failed: " . $response->body());
        }
    }

    /**
     * Paginate through @odata.nextLink automatically.
     */
    private function paginate(string $endpoint, array $query = []): array
    {
        $results = [];
        $query   = array_merge(['$top' => 999], $query);
        $url     = $this->baseUrl . $endpoint;

        do {
            $token    = $this->getAccessToken();
            $response = Http::withToken($token)->get($url, $url === $this->baseUrl . $endpoint ? $query : []);

            if (!$response->successful()) {
                throw new \RuntimeException("Graph paginate {$endpoint} failed: " . $response->body());
            }

            $body    = $response->json();
            $results = array_merge($results, $body['value'] ?? []);
            $url     = $body['@odata.nextLink'] ?? null;
        } while ($url);

        return $results;
    }

    // ─────────────────────────────────────────────────────────────
    // Test Connection
    // ─────────────────────────────────────────────────────────────

    public function testConnection(): string
    {
        $org = $this->get('/organization');
        return $org['value'][0]['displayName'] ?? 'Connected';
    }

    // ─────────────────────────────────────────────────────────────
    // User Operations
    // ─────────────────────────────────────────────────────────────

    public function listUsers(): array
    {
        return $this->paginate('/users', [
            '$select' => 'id,displayName,userPrincipalName,mail,jobTitle,department,accountEnabled,assignedLicenses,usageLocation',
            '$expand' => 'memberOf($select=id)',
        ]);
    }

    public function getUser(string $id): array
    {
        return $this->get("/users/{$id}", [
            '$select' => 'id,displayName,userPrincipalName,mail,jobTitle,department,accountEnabled,assignedLicenses,memberOf,usageLocation',
            '$expand' => 'memberOf',
        ]);
    }

    public function updateUser(string $id, array $data): void
    {
        $this->patch("/users/{$id}", $data);
    }

    public function disableUser(string $id): void
    {
        $this->patch("/users/{$id}", ['accountEnabled' => false]);
    }

    public function enableUser(string $id): void
    {
        $this->patch("/users/{$id}", ['accountEnabled' => true]);
    }

    public function resetPassword(string $id, string $newPassword, bool $forceChange = true): void
    {
        $this->patch("/users/{$id}", [
            'passwordProfile' => [
                'password'                      => $newPassword,
                'forceChangePasswordNextSignIn' => $forceChange,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // License Operations
    // ─────────────────────────────────────────────────────────────

    public function listSubscribedSkus(): array
    {
        $result = $this->get('/subscribedSkus');
        return $result['value'] ?? [];
    }

    public function assignLicense(string $userId, string $skuId): void
    {
        $this->post("/users/{$userId}/assignLicense", [
            'addLicenses'    => [['skuId' => $skuId, 'disabledPlans' => []]],
            'removeLicenses' => [],
        ]);
    }

    public function removeLicense(string $userId, string $skuId): void
    {
        $this->post("/users/{$userId}/assignLicense", [
            'addLicenses'    => [],
            'removeLicenses' => [$skuId],
        ]);
    }

    public function getUserLicenses(string $userId): array
    {
        $user = $this->get("/users/{$userId}", ['$select' => 'assignedLicenses']);
        return $user['assignedLicenses'] ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    // Group Operations
    // ─────────────────────────────────────────────────────────────

    public function listGroups(): array
    {
        return $this->paginate('/groups', [
            '$select' => 'id,displayName,description,groupTypes,mailEnabled,securityEnabled',
        ]);
    }

    public function listUserGroups(string $userId): array
    {
        $result = $this->get("/users/{$userId}/memberOf", ['$select' => 'id,displayName']);
        return $result['value'] ?? [];
    }

    public function addUserToGroup(string $userId, string $groupId): void
    {
        $this->post("/groups/{$groupId}/members/\$ref", [
            '@odata.id' => "https://graph.microsoft.com/v1.0/directoryObjects/{$userId}",
        ]);
    }

    public function removeUserFromGroup(string $userId, string $groupId): void
    {
        $this->delete("/groups/{$groupId}/members/{$userId}/\$ref");
    }
}
