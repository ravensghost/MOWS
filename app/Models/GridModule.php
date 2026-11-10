<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridModule extends Model
{
    protected $fillable = ['grid_id', 'grid_modules_title', 'col_span', 'row_span', 'grid_modules_sort_order'];

    public function grid(): BelongsTo
    {
        return $this->belongsTo(Grid::class);
    }
}