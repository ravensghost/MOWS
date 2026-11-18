<?php
// === app/Models/Shelf.php ===

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends Model
{
    protected $table = 'shelves';

    protected $fillable = [
        'shelf_title',
        'shelf_sort_order',
        'is_confidential',
    ];

    protected $casts = [
        'shelf_sort_order' => 'integer',
        'is_confidential'  => 'boolean',
    ];

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class, 'shelf_id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        if (auth()->check() && auth()->user()->can('manage_stories')) {
            return $query;
        }

        return $query->where('is_confidential', false);
    }
}