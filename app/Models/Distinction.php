<?php
// === app/Models/Distinction.php ===

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distinction extends Model
{
    use HasFactory;

    protected $table = 'distinctions';

    protected $fillable = [
        'distinction_title',
        'distinction_description',
        'distinction_base_colour',
        'mid_layer_mode',
        'mid_layer_asset',
        'mid_layer_modifier',
        'mid_layer_hue',
        'top_layer_mode',
        'top_layer_asset',
        'top_layer_modifier',
        'top_layer_hue',
        'scope_target',
        'distinction_catalogue_id',
    ];

    public function chronologicalRegisters(): HasMany
    {
        return $this->hasMany(ChronologicalRegister::class, 'distinction_id');
    }

    public function catalogue(): BelongsTo
    {
        return $this->belongsTo(DistinctionCatalogue::class, 'distinction_catalogue_id');
    }
}