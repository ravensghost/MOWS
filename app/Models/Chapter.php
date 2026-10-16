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
}