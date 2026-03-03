<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkCheck extends Model
{
    protected $fillable = [
        'host_id',
        'latency',
        'packet_loss',
        'success',
        'checked_at',
    ];

    protected $casts = [
        'latency' => 'float',
        'packet_loss' => 'float',
        'success' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(MonitoredHost::class, 'host_id');
    }
}
