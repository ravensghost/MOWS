<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChronologicalEngine extends Model
{
    protected $table = 'chronological_engines';

    protected $fillable = [
        'engine_title',
        'calculation_algorithm',
        'date_prefix',
        'date_suffix',
        'base_earth_year',
        'base_system_value',
        'temporal_multiplier',
        'is_active',
    ];
}
