<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LexiconCodex extends Model
{
    protected $table = 'lexicon_codex';

    protected $fillable = [
        'semantic_token',
        'prefix_group_title',
        'group_title',
        'default_lexicon_string',
        'translation_payloads',
    ];

    protected $attributes = [
        'prefix_group_title' => 'NotDefinedRight',
        'group_title'        => 'NotDefinedRight',
    ];

    protected function casts(): array
    {
        return [
            'translation_payloads' => 'array',
        ];
    }
}
