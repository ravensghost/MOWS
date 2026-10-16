<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSystem extends Model
{
    protected $fillable = [
        'name',
        'engine_type',
        'prefix',
        'suffix',
        'base_earth_year',
        'base_system_value',
        'multiplier',
        'is_active'
    ];
}
