<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredHost extends Model
{
    protected $fillable = [
        'branch_id',
        'vpn_id',
        'name',
        'ip',
        'type',
        'snmp_enabled',
        'snmp_version',
        'snmp_community',
        'status',
        'last_checked_at',
    ];

    protected $casts = [
        'snmp_enabled' => 'boolean',
        'snmp_community' => 'encrypted',
        'last_checked_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vpnTunnel(): BelongsTo
    {
        return $this->belongsTo(VpnTunnel::class, 'vpn_id');
    }

    public function networkChecks(): HasMany
    {
        return $this->hasMany(NetworkCheck::class, 'host_id');
    }

    public function snmpSensors(): HasMany
    {
        return $this->hasMany(SnmpSensor::class, 'host_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class, 'host_id');
    }
}
