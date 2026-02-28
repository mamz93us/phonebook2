<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkPort extends Model
{
    protected $fillable = [
        'switch_serial',
        'port_id',
        'name',
        'enabled',
        'type',
        'vlan',
        'allowed_vlans',
        'poe_enabled',
        'is_uplink',
        'status',
        'speed',
        'duplex',
        'client_mac',
        'client_hostname',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'poe_enabled'=> 'boolean',
        'is_uplink'  => 'boolean',
        'vlan'       => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function networkSwitch(): BelongsTo
    {
        return $this->belongsTo(NetworkSwitch::class, 'switch_serial', 'serial');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return strtolower($this->status ?? '') === 'connected';
    }

    public function isDisabled(): bool
    {
        return !$this->enabled || strtolower($this->status ?? '') === 'disabled';
    }

    /**
     * Bootstrap colour class for the port tile.
     */
    public function tileBgClass(): string
    {
        if (!$this->enabled) {
            return 'bg-secondary bg-opacity-25';
        }

        if ($this->is_uplink) {
            return 'bg-primary bg-opacity-75';
        }

        return match (strtolower($this->status ?? '')) {
            'connected'    => 'bg-success',
            'disconnected' => 'bg-secondary bg-opacity-50',
            'disabled'     => 'bg-secondary bg-opacity-25',
            default        => 'bg-secondary bg-opacity-50',
        };
    }

    /**
     * Human-readable port label (name or port_id).
     */
    public function label(): string
    {
        return $this->name ?: "Port {$this->port_id}";
    }

    /**
     * Speed display (e.g., "1 Gbps").
     */
    public function speedLabel(): string
    {
        if (!$this->speed) {
            return '-';
        }
        // Meraki returns "1000" (Mbps) or "10000" or "100"
        $mbps = (int) $this->speed;
        if ($mbps >= 1000) {
            return round($mbps / 1000, 1) . ' Gbps';
        }
        return $mbps . ' Mbps';
    }
}
