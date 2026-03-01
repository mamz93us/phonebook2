<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $fillable = [
        'type',
        'name',
        'model',
        'serial_number',
        'mac_address',
        'ip_address',
        'branch_id',
        'location_description',
        'notes',
        'source',
        'source_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function printer(): HasOne
    {
        return $this->hasOne(Printer::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function typeLabel(): string
    {
        return match ($this->type) {
            'ucm'      => 'UCM / IPPBX',
            'switch'   => 'Network Switch',
            'router'   => 'Router',
            'firewall' => 'Firewall',
            'ap'       => 'Access Point',
            'printer'  => 'Printer',
            'server'   => 'Server',
            default    => 'Other',
        };
    }

    public function typeIcon(): string
    {
        return match ($this->type) {
            'ucm'      => 'bi-telephone-fill',
            'switch'   => 'bi-hdd-network',
            'router'   => 'bi-router-fill',
            'firewall' => 'bi-shield-lock-fill',
            'ap'       => 'bi-wifi',
            'printer'  => 'bi-printer-fill',
            'server'   => 'bi-server',
            default    => 'bi-cpu',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'ucm'      => 'bg-primary',
            'switch'   => 'bg-info text-dark',
            'router'   => 'bg-warning text-dark',
            'firewall' => 'bg-danger',
            'ap'       => 'bg-success',
            'printer'  => 'bg-secondary',
            'server'   => 'bg-dark',
            default    => 'bg-secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active'      => 'bg-success',
            'retired'     => 'bg-secondary',
            'maintenance' => 'bg-warning text-dark',
            default       => 'bg-secondary',
        };
    }

    /**
     * Find or create a device record from a Meraki switch sync.
     */
    public static function syncFromMeraki(NetworkSwitch $switch): self
    {
        return self::updateOrCreate(
            ['source' => 'meraki', 'source_id' => $switch->serial],
            [
                'type'          => 'switch',
                'name'          => $switch->name ?: $switch->serial,
                'model'         => $switch->model,
                'serial_number' => $switch->serial,
                'mac_address'   => $switch->mac,
                'ip_address'    => $switch->lan_ip,
                'branch_id'     => $switch->branch_id,
                'status'        => $switch->status === 'online' ? 'active' : 'active',
            ]
        );
    }
}
