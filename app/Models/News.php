<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = ['title', 'content', 'author_id', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author() {
        return $this->belongsTo(User::class, 'author_id');
    }
}