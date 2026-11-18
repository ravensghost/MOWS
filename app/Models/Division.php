<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Division extends Model
{
    protected $table = 'divisions';

    protected $guarded = [];

    protected $casts = [
        'division_catalogue_id' => 'integer',
        'insignia_catalogue_id' => 'integer',
        'division_sort_order'   => 'integer',
        'is_visible'            => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('widget_roster_sections'));
        static::deleted(fn () => Cache::forget('widget_roster_sections'));
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'division_id');
    }

    public function positions(): HasMany
    {
        return $this->assignments();
    }

    public function insigniaCatalogue(): BelongsTo
    {
        return $this->belongsTo(InsigniaCatalogue::class, 'insignia_catalogue_id');
    }

    public function rankSet(): BelongsTo
    {
        return $this->insigniaCatalogue();
    }

    public function divisionCatalogue(): BelongsTo
    {
        return $this->belongsTo(DivisionCatalogue::class, 'division_catalogue_id');
    }
}