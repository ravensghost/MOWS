<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridModule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'grid_modules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'grid_id',
        'grid_module_title',
        'column_span',
        'row_span',
        'grid_module_sort_order',
    ];

    /**
     * Get the grid that owns the grid module.
     */
    public function grid(): BelongsTo
    {
        return $this->belongsTo(Grid::class, 'grid_id');
    }

    /**
     * Get the layout (grid) associated with the grid module.
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(Grid::class, 'grid_id');
    }
}
