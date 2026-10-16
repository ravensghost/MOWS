<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'widget_key',
        'default_options',
    ];

    protected $casts = [
        'default_options' => 'array', // Automatically casts JSON to an associative array
    ];

    /**
     * The pages this widget is attached to.
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_widget')
            ->withPivot(['id', 'layout_block_id', 'sort_order', 'instance_options', 'required_permission'])
            ->withTimestamps();
    }
}
