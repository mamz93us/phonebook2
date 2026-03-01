<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Printer extends Model
{
    protected $fillable = [
        'device_id',
        'printer_name',
        'manufacturer',
        'model',
        'serial_number',
        'mac_address',
        'ip_address',
        'branch_id',
        'floor_id',
        'office_id',
        'department_id',
        // Legacy free-text fields (kept for migration compatibility)
        'floor',
        'room',
        'department',
        'toner_model',
        'snmp_community',
        'snmp_version',
        'notes',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function networkFloor(): BelongsTo
    {
        return $this->belongsTo(NetworkFloor::class, 'floor_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(NetworkOffice::class, 'office_id');
    }

    public function departmentModel(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Location string using structured FK fields when available, falling back to legacy strings.
     */
    public function locationLabel(): string
    {
        $parts = [];

        if ($this->office) {
            $parts[] = $this->office->name;
        } elseif ($this->room) {
            $parts[] = $this->room;
        }

        if ($this->networkFloor) {
            array_unshift($parts, $this->networkFloor->name);
        } elseif ($this->floor) {
            array_unshift($parts, $this->floor);
        }

        return implode(' / ', $parts) ?: '—';
    }

    /**
     * Department name, from FK first, then legacy string.
     */
    public function departmentLabel(): string
    {
        return $this->departmentModel?->name ?? $this->department ?? '—';
    }

    /**
     * Number of credentials linked through the device.
     */
    public function credentialsCount(): int
    {
        return $this->device?->credentials()->count() ?? 0;
    }
}
