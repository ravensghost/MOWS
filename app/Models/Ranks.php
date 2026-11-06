<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranks extends Model
{
    protected $fillable = [
        'rank_set_id',
        'name',
        'short_name',
        'mid_type',
        'mid_image',
        'mid_css',
        'mid_color',
        'top_type',
        'top_image',
        'top_css',
        'top_color',
        'description',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function rankSet(): BelongsTo
    {
        return $this->belongsTo(RankSet::class);
    }
}
