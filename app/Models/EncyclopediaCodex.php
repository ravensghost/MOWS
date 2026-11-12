<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->article_url)) {
                $article->article_url = Str::slug($article->article_title);
            }
        });
    }
}
