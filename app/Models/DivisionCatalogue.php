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
    ];

    public function divisions(): HasMany
    {
        return $this->hasMany(Section::class, 'division_catalogue_id');
    }

    public function sections(): HasMany
    {
        return $this->divisions();
    }
}
