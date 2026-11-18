<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ChronologicalEngine extends Model
{
    protected $table = 'chronological_engines';

    protected $fillable = [
        'engine_title',
        'calculation_algorithm',
        'date_prefix',
        'date_suffix',
        'base_earth_year',
        'base_earth_modifier',
        'base_system_value',
        'temporal_multiplier',
        'is_active',
    ];

    protected $casts = [
        'base_earth_year'     => 'integer',
        'base_earth_modifier' => 'float',
        'base_system_value'   => 'float',
        'temporal_multiplier' => 'float',
        'is_active'           => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('active_time_system');
        });

        static::deleted(function () {
            Cache::forget('active_time_system');
        });
    }

    public function missionEvents(): HasMany
    {
        return $this->hasMany(MissionCalendar::class, 'chronological_engine_id');
    }
}