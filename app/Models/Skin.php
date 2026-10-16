<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skin extends Model
{
   protected $fillable = [
    'name', 
    'folder_name', 
    'version', 
    'is_active', 
    'is_global_default',
    'custom_head' // Add this line
];
}
