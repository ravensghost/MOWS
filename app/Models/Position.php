<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['section_id', 'name', 'description', 'is_open', 'capacity', 'is_visible'];

    protected $casts = [
        'is_open' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }
}
