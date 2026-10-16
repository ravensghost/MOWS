<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    // Standard Laravel mass-assignment protection
    // app/Models/Term.php

    protected $fillable = [
        'code_key',
        'master_group',
        'subgroup',
        'translations'
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
        ];
    }
}