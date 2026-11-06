<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function rankSet(): BelongsTo
    {
        return $this->belongsTo(RankSet::class);
    }

    public function divisionCatalogue(): BelongsTo
    {
        return $this->belongsTo(DivisionCatalogue::class, 'division_catalogue_id');
    }
}
