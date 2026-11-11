<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insignia extends Model
{
    protected $table = 'insignia';

    protected $fillable = [
        'insignia_catalogue_id',
        'insignia_title',
        'abbreviated_title',
        'mid_layer_mode',
        'mid_layer_asset',
        'mid_layer_modifier',
        'mid_layer_hue',
        'top_layer_mode',
        'top_layer_asset',
        'top_layer_modifier',
        'top_layer_hue',
        'insignia_description',
        'insignia_sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function insigniaCatalogue(): BelongsTo
    {
        return $this->belongsTo(InsigniaCatalogue::class, 'insignia_catalogue_id');
    }

    public function rankSet(): BelongsTo
    {
        return $this->belongsTo(InsigniaCatalogue::class, 'insignia_catalogue_id');
    }
}
