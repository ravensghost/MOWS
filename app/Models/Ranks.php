<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ranks extends Model
{
    protected $fillable = [
        'rank_set_id', 
        'name', 
        'short_name', 
        'mid_type',
        'mid_image',
        'mid_css',
        'top_type',
        'top_image',
        'top_css',
        'description', 
        'sort_order'
    ];

    public function rankSet() {
        return $this->belongsTo(RankSet::class);
    }
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'rank_id');
    }
}