<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'company_logo',
        'sso_enabled',
        'sso_tenant_id',
        'sso_client_id',
        'sso_client_secret',
        'sso_default_role',
        // Meraki Network
        'meraki_enabled',
        'meraki_api_key',
        'meraki_org_id',
        'meraki_polling_interval',
    ];

    protected $casts = [
        'sso_enabled'             => 'boolean',
        'meraki_enabled'          => 'boolean',
        'meraki_polling_interval' => 'integer',
    ];

    /**
     * Get the singleton settings record
     */
    public static function get(): static
    {
        return static::first() ?? static::create([
            'company_name'     => 'Company Name',
            'company_logo'     => null,
            'sso_enabled'      => false,
            'sso_default_role' => 'viewer',
        ]);
    }

    // ─── SSO client secret — encrypted at rest ────────────────────

    public function setSsoClientSecretAttribute(?string $value): void
    {
        $this->attributes['sso_client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSsoClientSecretAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    // ─── Meraki API key — encrypted at rest ──────────────────────

    public function setMerakiApiKeyAttribute(?string $value): void
    {
        $this->attributes['meraki_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getMerakiApiKeyAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }
}
