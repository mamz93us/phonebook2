<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkClient extends Model
{
    protected $fillable = [
        'client_id',
        'switch_serial',
        'mac',
        'ip',
        'hostname',
        'description',
        'vlan',
        'port_id',
        'status',
        'usage_kb',
        'manufacturer',
        'os',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'vlan'      => 'integer',
        'usage_kb'  => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function networkSwitch(): BelongsTo
    {
        return $this->belongsTo(NetworkSwitch::class, 'switch_serial', 'serial');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isOnline(): bool
    {
        return strtolower($this->status ?? '') === 'online';
    }

    public function displayName(): string
    {
        return $this->hostname ?: $this->description ?: $this->mac;
    }

    /**
     * Human-readable usage (KB → MB → GB).
     */
    public function usageLabel(): string
    {
        if (!$this->usage_kb) {
            return '-';
        }
        if ($this->usage_kb >= 1_000_000) {
            return round($this->usage_kb / 1_000_000, 1) . ' GB';
        }
        if ($this->usage_kb >= 1_000) {
            return round($this->usage_kb / 1_000, 1) . ' MB';
        }
        return $this->usage_kb . ' KB';
    }

    /**
     * Status badge class.
     */
    public function statusBadgeClass(): string
    {
        return strtolower($this->status ?? '') === 'online' ? 'bg-success' : 'bg-secondary';
    }
}
