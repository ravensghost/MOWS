<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTier extends Model
{
    protected $table = 'content_tiers';

    public $timestamps = false;

    protected $fillable = [
        'tier_category',
        'tier_level',
        'tier_label',
        'category_sort_order',
        'tier_sort_order',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'tier_level'          => 'integer',
        'category_sort_order' => 'integer',
        'tier_sort_order'     => 'integer',
    ];
}
