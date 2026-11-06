<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends Model
{
    protected $table = 'shelves';

    protected $fillable = [
        'shelf_title',
        'shelf_sort_order',
    ];

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class, 'shelf_id');
    }
}
