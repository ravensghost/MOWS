<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Page extends Model
{
    protected $guarded = [];

    /**
     * The grid layout assigned to this page.
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    /**
     * The form fields / widgets assigned to this page's layout blocks.
     */
    public function widgets(): BelongsToMany
    {
        return $this->belongsToMany(Widget::class, 'page_widget')
            ->withPivot(['id', 'layout_block_id', 'sort_order', 'instance_options', 'required_permission'])
            ->withCasts(['instance_options' => 'array']) // Automatically decodes the JSON payload into a PHP array
            ->orderBy('page_widget.sort_order', 'asc')
            ->withTimestamps();
    }
}
