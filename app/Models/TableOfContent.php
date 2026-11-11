<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class TableOfContent extends Model
{
    protected $table = 'table_of_contents';

    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('table_of_contents_all_array');
        });

        static::deleted(function () {
            Cache::forget('table_of_contents_all_array');
        });
    }

    /**
     * Child menu items linked via parent_entry_id.
     */
    public function children(): HasMany
    {
        return $this->hasMany(TableOfContent::class, 'parent_entry_id')
            ->orderBy('entry_sort_order');
    }

    /**
     * Parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TableOfContent::class, 'parent_entry_id');
    }
}
