<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * Normalise any book URL to MOWS canonical leading-slash format.
     */
    public static function normaliseUrl(string $url): string
    {
        $trimmed = trim($url);

        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/' . ltrim($trimmed, '/');
    }

    /**
     * Query a book by one or more URLs, automatically enforcing leading slashes.
     *
     * @param  string|array<int, string>  $urls
     */
    public function scopeForUrl(Builder $query, string|array $urls): Builder
    {
        if (is_array($urls)) {
            $normalised = array_map(static fn (string $u): string => self::normaliseUrl($u), $urls);

            return $query->whereIn('book_url', $normalised);
        }

        return $query->where('book_url', self::normaliseUrl($urls));
    }

    /**
     * Scope to published books with eager-loaded grid blocks and visible ordered modules.
     */
    public function scopePublishedWithLayout(Builder $query): Builder
    {
        return $query->where('is_published', true)->with([
            'grid.gridModules',
            'modules' => function ($moduleQuery) {
                $moduleQuery->wherePivot('is_visible', true)
                    ->orderBy('book_modules.module_sort_order');
            },
        ]);
    }

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