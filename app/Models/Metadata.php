<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $table = 'metadata';

    protected $guarded = [];

    protected $casts = [
        'metadata_payloads' => 'array',
        'is_required'       => 'boolean',
        'is_visible'        => 'boolean',
        'meta_mode'         => 'string',
    ];
}
