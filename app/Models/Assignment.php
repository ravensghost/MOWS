<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Assignment extends Model
{
    protected $table = 'assignments';

    protected $fillable = [
        'division_id',
        'assignment_title',
        'assignment_synopsis',
        'maximum_occupants',
        'is_recruitment_open',
        'assignment_sort_order',
        'is_visible',
    ];

    protected $casts = [
        'division_id'           => 'integer',
        'maximum_occupants'     => 'integer',
        'is_recruitment_open'   => 'boolean',
        'assignment_sort_order' => 'integer',
        'is_visible'            => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('widget_roster_sections'));
        static::deleted(fn () => Cache::forget('widget_roster_sections'));
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function section(): BelongsTo
    {
        return $this->division();
    }

    public function personae(): HasMany
    {
        return $this->hasMany(Persona::class, 'assignment_id');
    }

    public function characters(): HasMany
    {
        return $this->personae();
    }
}