<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DivisionCatalogue extends Model
{
    protected $table = 'division_catalogues';

    protected $fillable = [
        'catalogue_title',
        'catalogue_description',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class, 'division_catalogue_id');
    }
}