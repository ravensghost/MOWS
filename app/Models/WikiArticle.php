<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WikiArticle extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'content',
        'is_visible',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }
}
