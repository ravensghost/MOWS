<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Page extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
    ];

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
            ->withPivot(['id', 'layout_block_id', 'sort_order', 'instance_options', 'required_permission', 'is_visible'])
            ->withCasts([
                'instance_options' => 'array',
                'is_visible' => 'boolean',
            ])
            ->orderBy('page_widget.sort_order', 'asc')
            ->withTimestamps();
    }

    /**
     * Only the visible widgets assigned to this page's layout blocks for public rendering.
     */
    public function visibleWidgets(): BelongsToMany
    {
        return $this->widgets()->wherePivot('is_visible', true);
    }
}
