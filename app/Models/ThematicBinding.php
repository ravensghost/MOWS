<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThematicBinding extends Model
{
    protected $table = 'thematic_bindings';

    protected $fillable = [
        'binding_title',
        'directory_name',
        'package_version',
        'is_active',
        'is_global_default',
        'custom_head_injection',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'is_global_default' => 'boolean',
    ];
}
