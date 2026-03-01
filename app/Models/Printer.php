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

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Location string: Floor / Room
     */
    public function locationLabel(): string
    {
        $parts = array_filter([$this->floor, $this->room]);
        return implode(' / ', $parts) ?: '—';
    }

    /**
     * Number of credentials linked through the device.
     */
    public function credentialsCount(): int
    {
        return $this->device?->credentials()->count() ?? 0;
    }
}
