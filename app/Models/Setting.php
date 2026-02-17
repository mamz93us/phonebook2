<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'company_logo',
    ];

    /**
     * Get the singleton settings record
     */
    public static function get()
    {
        return static::first() ?? static::create([
            'company_name' => 'Company Name',
            'company_logo' => null,
        ]);
    }
}
