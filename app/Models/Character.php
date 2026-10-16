<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function rank()
    {
        return $this->belongsTo(Ranks::class, 'rank_id');
    }

    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class, 'active_character_id');
    }

    public function featuredChapters()
    {
        return $this->belongsToMany(Chapter::class, 'chapter_character', 'character_id', 'chapter_id');
    }

    public function attributes()
    {
        return $this->hasMany(CharacterAttribute::class);
    }

    // Added relationship to the character's application
    public function application()
    {
        return $this->hasOne(Application::class, 'character_id');
    }
}
