<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsigniaCatalogue extends Model
{
    protected $table = 'insignia_catalogues';

    protected $fillable = [
        'catalogue_title',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function insignia()
    {
        return $this->hasMany(Insignia::class, 'insignia_catalogue_id');
    }

    public function ranks()
    {
        return $this->hasMany(Insignia::class, 'insignia_catalogue_id');
    }

    public function divisions()
    {
        return $this->hasMany(Division::class, 'insignia_catalogue_id');
    }

    public function sections()
    {
        return $this->hasMany(Division::class, 'insignia_catalogue_id');
    }
}
