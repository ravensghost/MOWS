<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;

    protected $table = 'modules';

    protected $fillable = [
        'module_title',
        'module_token',
        'module_default_settings',
    ];

    protected $casts = [
        'module_default_settings' => 'array',
    ];

    /**
     * The books this module is attached to.
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_modules', 'module_id', 'book_id')
            ->withPivot([
                'id',
                'grid_module_id',
                'module_sort_order',
                'book_config',
                'access_clearance',
                'is_visible',
            ])
            ->withCasts([
                'book_config' => 'array',
                'is_visible'  => 'boolean',
            ])
            ->withTimestamps();
    }
}
