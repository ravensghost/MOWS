<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $guarded = [];

    public function positions() {
        return $this->hasMany(Position::class);
    }
    public function rankSet() { 
        return $this->belongsTo(RankSet::class); 
    }
}