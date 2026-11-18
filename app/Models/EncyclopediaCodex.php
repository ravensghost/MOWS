<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EncyclopediaCodex extends Model
{
    protected $table = 'encyclopedia_codex';

    protected $fillable = [
        'article_title',
        'article_url',
        'article_category',
        'article_text',
        'is_visible',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EncyclopediaCodex $article) {
            if (empty($article->article_url)) {
                $article->article_url = Str::slug($article->article_title);
            }
            if (empty($article->created_by_user_id) && auth()->check()) {
                $article->created_by_user_id = auth()->id();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}