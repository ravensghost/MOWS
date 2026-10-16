<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationOption extends Model
{
    protected $fillable = [
        'option_type',
        'value',
        'label',
        'sort_order',
    ];
}
