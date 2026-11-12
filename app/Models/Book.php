<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    protected $table = 'books';
    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * The grid assigned to this book.
     */
    public function grid(): BelongsTo
    {
        return $this->belongsTo(Grid::class, 'grid_id');
    }

    /**
     * The modules assigned to this book's grid modules.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'book_modules', 'book_id', 'module_id')
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
            ->orderByPivot('module_sort_order', 'asc')
            ->withTimestamps();
    }

    /**
     * Singular alias for modules.
     */
    public function module(): BelongsToMany
    {
        return $this->modules();
    }

    /**
     * Only the visible modules assigned to this book's grid modules.
     */
    public function visibleModules(): BelongsToMany
    {
        return $this->modules()->wherePivot('is_visible', true);
    }
}
