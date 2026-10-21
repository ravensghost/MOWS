<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'character_name',
        'user_id',
        'character_id',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(ApplicationAnswer::class);
    }
    public function votes()
    {
        return $this->hasMany(ApplicationVote::class);
    }
}
