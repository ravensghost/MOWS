<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistinctionCatalogue extends Model
{
    protected $table = 'distinction_catalogues';

    protected $fillable = ['catalogue_title'];

    public function distinctions(): HasMany
    {
        return $this->hasMany(Distinction::class, 'distinction_catalogue_id');
    }

    public function awards(): HasMany
    {
        return $this->distinctions();
    }
}
