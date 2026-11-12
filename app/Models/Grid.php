<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grid extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'grids';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'grid_title',
        'grid_synopsis',
    ];

    /**
     * Retrieve the grid modules ordered by their sort order.
     */
    public function gridModules(): HasMany
    {
        return $this->hasMany(GridModule::class, 'grid_id')->orderBy('grid_module_sort_order');
    }

    /**
     * Retrieve the books associated with the grid.
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'grid_id');
    }
}
