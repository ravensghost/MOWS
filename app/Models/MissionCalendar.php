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
        'event_synopsis',
        'gregorian_start_date',
        'gregorian_end_date',
        'in_universe_date_string',
        'volume_id',
        'folio_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'chronological_engine_id' => 'integer',
        'gregorian_start_date'    => 'datetime',
        'gregorian_end_date'      => 'datetime',
        'volume_id'               => 'integer',
        'folio_id'                => 'integer',
        'created_by_user_id'      => 'integer',
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

    /**
     * The story volume linked to this scheduled calendar event.
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volume_id');
    }

    /**
     * The specific folio linked to this scheduled calendar event.
     */
    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'folio_id');
    }

    /**
     * The user account that created this scheduled calendar event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}