<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    // Manual IDs
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'phone_number',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function networkFloors()
    {
        return $this->hasMany(NetworkFloor::class)->orderBy('sort_order')->orderBy('name');
    }

    public function networkSwitches()
    {
        return $this->hasMany(NetworkSwitch::class);
    }
}
