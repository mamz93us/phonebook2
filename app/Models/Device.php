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
        'floor_id',
        'office_id',
        'department_id',
        'location_description',
        'notes',
        'source',
        'source_id',
        'status',
        'purchase_date',
        'warranty_expiry',
    ];

    protected $casts = [
        'status'          => 'string',
        'purchase_date'   => 'date',
        'warranty_expiry' => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(NetworkFloor::class, 'floor_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(NetworkOffice::class, 'office_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function printer(): HasOne
    {
        return $this->hasOne(Printer::class);
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAsset::class, 'asset_id');
    }

    public function currentAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeAsset::class, 'asset_id')->whereNull('returned_date');
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
            'assigned'    => 'bg-primary',
            'available'   => 'bg-success',
            'repair'      => 'bg-warning text-dark',
            'retired'     => 'bg-secondary',
            'maintenance' => 'bg-warning text-dark',
            default       => 'bg-secondary',
        };
    }

    public function isWarrantyExpired(): bool
    {
        if (!$this->warranty_expiry) return false;
        return $this->warranty_expiry->isPast();
    }

    public function warrantyDaysLeft(): ?int
    {
        if (!$this->warranty_expiry) return null;
        return (int) now()->diffInDays($this->warranty_expiry, false);
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
