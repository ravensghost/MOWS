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
}