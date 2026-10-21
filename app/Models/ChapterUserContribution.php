<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterUserContribution extends Model
{
    public $timestamps = false; // if your table doesn't use created_at/updated_at

    protected $fillable = [
        'chapter_id',
        'user_id',
        'word_count',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}