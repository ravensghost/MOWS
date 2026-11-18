<?php

namespace App\Models;

use App\Models\Traits\HasChronologicalTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionCalendar extends Model
{
    use HasChronologicalTime;

    protected $table = 'mission_calendar';

    protected $fillable = [
        'chronological_engine_id',
        'event_title',
        'description',
        'gregorian_start_date',
        'in_universe_date_string',
    ];

    protected $casts = [
        'gregorian_start_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (MissionCalendar $event) {
            if (empty($event->in_universe_date_string) && $event->gregorian_start_date && function_exists('world_date')) {
                $event->in_universe_date_string = world_date($event->gregorian_start_date);
            }
        });
    }

    /**
     * The chronological engine used to compute this event's in-universe date.
     */
    public function chronologicalEngine(): BelongsTo
    {
        return $this->belongsTo(ChronologicalEngine::class, 'chronological_engine_id');
    }
}