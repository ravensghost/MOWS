<?php
// === app/Models/GridModule.php ===

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridModule extends Model
{
    protected $table = 'grid_modules';

    protected $fillable = [
        'grid_id',
        'grid_module_title',
        'column_span',
        'row_span',
        'grid_module_sort_order',
    ];

    protected $casts = [
        'grid_id'                => 'integer',
        'column_span'            => 'integer',
        'row_span'               => 'integer',
        'grid_module_sort_order' => 'integer',
    ];

    public function grid(): BelongsTo
    {
        return $this->belongsTo(Grid::class, 'grid_id');
    }
}