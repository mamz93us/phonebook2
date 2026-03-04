<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnmpSensor extends Model
{
    protected $fillable = [
        'host_id',
        'name',
        'oid',
        'description',
        'data_type',
        'unit',
        'poll_interval',
        'warning_threshold',
        'critical_threshold',
        'graph_enabled',
    ];

    protected $casts = [
        'graph_enabled' => 'boolean',
        'warning_threshold' => 'float',
        'critical_threshold' => 'float',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(MonitoredHost::class, 'host_id');
    }

    public function sensorMetrics(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SensorMetric::class, 'sensor_id');
    }
}
