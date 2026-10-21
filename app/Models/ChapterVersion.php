<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChapterVersion extends Model
{
    protected $guarded = [];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
