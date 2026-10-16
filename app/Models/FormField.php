<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'form_type' => 'string',
    ];
    public function pages() { 
        return $this->belongsToMany(Page::class); 
    }
}