<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineageCatalogue extends Model
{
    protected $table = 'lineage_catalogues';

    protected $fillable = [
        'lineage_title',
        'lineage_synopsis',
        'visual_trait_prompt',
        'negative_visual_trait_prompt',
        'default_skin_tint_hex',
        'overlay_assets_json',
        'is_playable',
        'is_visible',
    ];

    protected $casts = [
        'overlay_assets_json' => 'array',
        'is_playable'         => 'boolean',
        'is_visible'          => 'boolean',
    ];

    /**
     * The personae belonging to this species / lineage catalogue.
     */
    public function personae(): HasMany
    {
        return $this->hasMany(Persona::class, 'lineage_catalogue_id');
    }
}