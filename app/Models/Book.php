<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $guarded = [];

    public function bookcase()
    {
        return $this->belongsTo(Bookcase::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function status()
    {
        return $this->belongsTo(BookStatus::class, 'status_id');
    }

    /**
     * Retrieve users subscribed/participating in this book (or active roster users).
     */
    public function getSubscribersAttribute()
    {
        $chapterIds = $this->chapters()->pluck('id');

        $userIds = Character::whereHas('chapters', function ($q) use ($chapterIds) {
            $q->whereIn('chapters.id', $chapterIds);
        })->whereNotNull('user_id')->pluck('user_id')
            ->merge($this->chapters()->whereNotNull('author_user_id')->pluck('author_user_id'))
            ->unique()
            ->filter();

        if ($userIds->isEmpty()) {
            return User::where('id', '!=', auth()->id())->get();
        }

        return User::whereIn('id', $userIds)->where('id', '!=', auth()->id())->get();
    }
}
