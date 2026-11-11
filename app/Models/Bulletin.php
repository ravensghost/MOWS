<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    protected $table = 'bulletins';

    protected $fillable = [
        'bulletin_title',
        'bulletin_text',
        'user_id',
        'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * The user account that authored this bulletin announcement.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
