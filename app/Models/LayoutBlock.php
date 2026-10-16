<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutBlock extends Model
{
    protected $fillable = ['layout_id', 'name', 'col_span', 'row_span', 'sort_order'];

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }
}