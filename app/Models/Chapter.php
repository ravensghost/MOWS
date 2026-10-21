<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $guarded = [];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function activeCharacter()
    {
        return $this->belongsTo(Character::class, 'active_character_id');
    }
    
    public function turnUser()
    {
        return $this->belongsTo(User::class, 'turn_user_id');
    }
    public function characters()
    {
        return $this->belongsToMany(Character::class);
    }
    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
    public function isAccessibleBy(?User $user): bool
    {
        // Published chapters are publicly viewable
        if ($this->status === 'published') {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Webmasters or users with global chapter edit permissions
        if ($user->hasRole('webmaster') || $user->can('edit_chapter')) {
            return true;
        }

        // Original author of the draft
        if ($this->author_user_id === $user->id) {
            return true;
        }

        // Check if the user owns any of the characters tagged as present in this chapter
        $coAuthorUserIds = $this->characters()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();

        return in_array($user->id, $coAuthorUserIds);
    }
    public function versions()
    {
        return $this->hasMany(ChapterVersion::class)->orderBy('created_at', 'desc');
    }
    // In app/Models/Chapter.php

    public function contributions()
    {
        return $this->hasMany(ChapterUserContribution::class);
    }

    public function contributingUsers()
    {
        return $this->belongsToMany(User::class, 'chapter_user_contributions', 'chapter_id', 'user_id')
            ->withPivot('word_count');
    }
}