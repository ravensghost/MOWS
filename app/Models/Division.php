<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $table = 'divisions';

    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'division_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Assignment::class, 'division_id');
    }

    public function insigniaCatalogue(): BelongsTo
    {
        return $this->belongsTo(InsigniaCatalogue::class, 'insignia_catalogue_id');
    }

    public function rankSet(): BelongsTo
    {
        return $this->belongsTo(InsigniaCatalogue::class, 'insignia_catalogue_id');
    }

    public function divisionCatalogue(): BelongsTo
    {
        return $this->belongsTo(DivisionCatalogue::class, 'division_catalogue_id');
    }
}
