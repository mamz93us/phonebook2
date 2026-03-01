<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityLicense extends Model
{
    protected $fillable = [
        'sku_id',
        'sku_part_number',
        'display_name',
        'total',
        'consumed',
        'available',
        'applies_to',
        'capability_status',
    ];

    protected $casts = [
        'total'    => 'integer',
        'consumed' => 'integer',
        'available'=> 'integer',
    ];

    public function usagePercent(): int
    {
        if ($this->total <= 0) {
            return 0;
        }
        return (int) round(($this->consumed / $this->total) * 100);
    }

    public function usageBarClass(): string
    {
        $pct = $this->usagePercent();
        if ($pct >= 90) return 'bg-danger';
        if ($pct >= 70) return 'bg-warning';
        return 'bg-success';
    }

    /**
     * Friendly display name (fall back to part number).
     */
    public function friendlyName(): string
    {
        $map = [
            'ENTERPRISEPACK'   => 'Microsoft 365 E3',
            'SPE_E5'           => 'Microsoft 365 E5',
            'EXCHANGESTANDARD' => 'Exchange Online (Plan 1)',
            'TEAMS_EXPLORATORY'=> 'Teams Exploratory',
        ];
        return $map[$this->sku_part_number] ?? $this->display_name;
    }
}
