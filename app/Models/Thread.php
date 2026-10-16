<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $fillable = ['type', 'title'];

    public function participants()
    {
        return $this->hasMany(ThreadParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}