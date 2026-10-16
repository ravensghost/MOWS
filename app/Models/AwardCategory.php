<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardCategory extends Model
{
    protected $fillable = ['name'];

    public function awards()
    {
        return $this->hasMany(Award::class);
    }
}
