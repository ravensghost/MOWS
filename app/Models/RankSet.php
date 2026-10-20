<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankSet extends Model
{
    protected $fillable = ['name', 'is_visible'];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function ranks()
    {
        return $this->hasMany(Ranks::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
