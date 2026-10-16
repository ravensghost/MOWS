<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRecord extends Model
{
    protected $guarded = [];
    public function award()
    {
        return $this->belongsTo(Award::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function character()
    {
        return $this->belongsTo(Character::class);
    }
    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
